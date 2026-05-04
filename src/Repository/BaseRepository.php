<?php

namespace EntityForge\Repository;

use EntityForge\Database\Connection;
use EntityForge\Tenant\TenantContext;
use EntityForge\Tenant\TenantGuard;
use EntityForge\Tenant\TenantConnectionResolver;
use Exception;
abstract class BaseRepository
{
    protected Connection $connection;
    protected string $table;

    public function __construct(array $config)
    {
        $this->connection = TenantConnectionResolver::resolve($config);
        $this->table = $this->resolveTableName();
    }

    protected function resolveTableName(): string
    {
        $class = (new \ReflectionClass($this))->getShortName();
        return strtolower(str_replace('Repository', '', $class)) . 's';
    }

    /**
     * @throws Exception
     */
    protected function getTenantId(): string
    {
        TenantGuard::ensureTenant();
        return TenantContext::getTenantId();
    }

    /**
     * @throws Exception
     */
    protected function applyTenantScope(array $data): array
    {
        $data['tenant_id'] = $this->getTenantId();
        return $data;
    }

    /**
     * @throws Exception
     */
    public function create(array $data): array
    {
        $data = $this->applyTenantScope($data);

        $columns = array_keys($data);
        $placeholders = array_map(fn($c)=> ':' . $c, $columns);

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders));

        $stmt = $this->connection->getPdo()->prepare($sql);
         $stmt->execute($data);

        return $data;
    }
}
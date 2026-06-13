<?php

namespace EntityForge\Repository;

use EntityForge\Database\Connection;
use EntityForge\Tenant\TenantConnectionResolver;
use EntityForge\Tenant\TenantContext;
use EntityForge\Tenant\TenantGuard;
use Exception;
use PDO;

abstract class BaseRepository
{
    protected Connection $connection;
    protected string $table;
    /** @var array<string, mixed> */
    protected array $config;

    /** @param array<string, mixed> $config */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->connection = TenantConnectionResolver::resolve($config);
        $this->table = $this->resolveTableName();
    }

    private function assertColumnName(string $column): void
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
            throw new \InvalidArgumentException("Invalid column name: '{$column}'");
        }
    }

    protected function resolveTableName(): string
    {
        $class = (new \ReflectionClass($this))
            ->getShortName();
        return strtolower(
                str_replace('Repository', '', $class)
            ) . 's';
    }

    /**
     * @throws Exception
     */
    protected function getTenantId(): string
    {
        TenantGuard::ensureTenant();
        return TenantContext::getTenantId() ?? throw new \LogicException('Tenant ID is null after guard check.');
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws Exception
     */
    protected function applyTenantScope(array $data): array
    {
        if ($this->shouldApplyTenantScope()) {
            $data['tenant_id'] = $this->getTenantId();
        }

        return $data;
    }

    /**
     * Determine if tenant scope should be applied
     */
    protected function shouldApplyTenantScope(): bool
    {
        return ($this->config['tenancy']['strategy'] ?? 'shared')
            === 'shared';
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws Exception
     */
    public function create(array $data): array
    {
        $data = $this->applyTenantScope($data);

        $columns = array_keys($data);

        array_walk($columns, fn(string $c) => $this->assertColumnName($c));

        $placeholders = array_map(
            fn(string $column) => ':' . $column,
            $columns
        );

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $statement = $this->connection
            ->getPdo()
            ->prepare($sql);

        $statement->execute($data);

        return $data;
    }

    /**
     * @return array<int, array<string, mixed>>
     * @throws Exception
     */
    public function findAll(): array
    {
        $sql = "SELECT * FROM {$this->table}";

        $params = [];

        if ($this->shouldApplyTenantScope()) {
            $sql .= " WHERE tenant_id = :tenant_id";

            $params['tenant_id'] = $this->getTenantId();
        }

        $statement = $this->connection
            ->getPdo()
            ->prepare($sql);

        $statement->execute($params);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array<string, mixed>|null
     * @throws Exception
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";

        $params = [
            'id' => $id,
        ];

        if ($this->shouldApplyTenantScope()) {
            $sql .= " AND tenant_id = :tenant_id";

            $params['tenant_id'] = $this->getTenantId();
        }

        $statement = $this->connection
            ->getPdo()
            ->prepare($sql);

        $statement->execute($params);

        $result = $statement->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * @param array<string, mixed> $conditions
     * @return array<int, array<string, mixed>>
     * @throws Exception
     */
    public function where(array $conditions): array
    {
        $clauses = [];

        $params = [];

        foreach ($conditions as $column => $value) {
            $this->assertColumnName($column);
            $clauses[] = "{$column} = :{$column}";
            $params[$column] = $value;
        }

        if ($this->shouldApplyTenantScope()) {
            $clauses[] = "tenant_id = :tenant_id";

            $params['tenant_id'] = $this->getTenantId();
        }

        $sql = sprintf(
            'SELECT * FROM %s WHERE %s',
            $this->table,
            implode(' AND ', $clauses)
        );

        $statement = $this->connection
            ->getPdo()
            ->prepare($sql);

        $statement->execute($params);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param array<string, mixed> $data
     * @throws Exception
     */
    public function update(int $id, array $data): bool
    {
        $setClauses = [];

        foreach (array_keys($data) as $column) {
            $this->assertColumnName($column);
            $setClauses[] = "{$column} = :{$column}";
        }

        $sql = sprintf(
            'UPDATE %s SET %s WHERE id = :id',
            $this->table,
            implode(', ', $setClauses)
        );

        $params = $data;

        $params['id'] = $id;

        if ($this->shouldApplyTenantScope()) {
            $sql .= " AND tenant_id = :tenant_id";

            $params['tenant_id'] = $this->getTenantId();
        }

        $statement = $this->connection
            ->getPdo()
            ->prepare($sql);

        return $statement->execute($params);
    }

    /**
     * Delete record by ID
     *
     * @throws Exception
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";

        $params = [
            'id' => $id,
        ];

        if ($this->shouldApplyTenantScope()) {
            $sql .= " AND tenant_id = :tenant_id";

            $params['tenant_id'] = $this->getTenantId();
        }

        $statement = $this->connection
            ->getPdo()
            ->prepare($sql);

        return $statement->execute($params);
    }

    public function beginTransaction(): void
    {
        $this->connection
            ->getPdo()
            ->beginTransaction();
    }

    public function commit(): void
    {
        $this->connection
            ->getPdo()
            ->commit();
    }

    public function rollback(): void
    {
        $this->connection
            ->getPdo()
            ->rollBack();
    }
}
<?php

namespace EntityForge\Tenant;

use EntityForge\Database\Connection;
use Exception;

class TenantRepository
{
    private Connection $connection;

    /**
     * @throws Exception
     */
    public function __construct(array $config)
    {
        $this->connection = new Connection($config['database']);
    }

    public function create(string $tenantId, string $name): void
    {
        $sql = "INSERT INTO tenants (tenant_id, name) VALUES (:tenant_id, :name)";

        $stmt = $this->connection->getPdo()->prepare($sql);
        $stmt->execute([
            'tenant_id' => $tenantId,
            'name' => $name
        ]);
    }

    public function all(): array
    {
        return $this->connection->getPdo()
            ->query("SELECT * FROM tenants")
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function exists(string $tenantId): bool
    {
        $stmt = $this->connection->getPdo()->prepare(
            "SELECT COUNT(*) FROM tenants WHERE tenant_id = :id"
        );

        $stmt->execute(['id' => $tenantId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function findByTenantId(string $tenantId): ?array
    {
        $stmt = $this->connection->getPdo()->prepare(
            "SELECT * FROM tenants WHERE tenant_id = :id LIMIT 1"
        );

        $stmt->execute(['id' => $tenantId]);

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function updateStatus(string $tenantId, string $status): void
    {
        $stmt = $this->connection->getPdo()->prepare(
            "UPDATE tenants SET status = :status WHERE tenant_id = :id"
        );

        $stmt->execute(['status' => $status, 'id' => $tenantId]);
    }

    public function deleteByTenantId(string $tenantId): void
    {
        $stmt = $this->connection->getPdo()->prepare(
            "DELETE FROM tenants WHERE tenant_id = :id"
        );

        $stmt->execute(['id' => $tenantId]);
    }
}
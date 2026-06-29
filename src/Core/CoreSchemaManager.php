<?php

namespace EntityForge\Core;

use EntityForge\Database\Connection;
use PDO;

class CoreSchemaManager
{
    private PDO $pdo;

    /** @param array<string, mixed> $config */
    public function __construct(array $config)
    {
        $this->pdo = (new Connection($config['database']))->getPdo();
    }

    public function ensure(): void
    {
        foreach ($this->definitions() as $sql) {
            $this->pdo->exec($sql);
        }
    }

    /**
     * @return array<int, string>
     */
    private function definitions(): array
    {
        return [
            $this->tenantsTable(),
            $this->tenantFieldsTable(),
        ];
    }

    private function tenantsTable(): string
    {
        return <<<SQL
CREATE TABLE IF NOT EXISTS tenants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
SQL;
    }

    private function tenantFieldsTable(): string
    {
        return <<<SQL
CREATE TABLE IF NOT EXISTS tenant_fields (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id  VARCHAR(255) NOT NULL,
    entity     VARCHAR(255) NOT NULL,
    field_name VARCHAR(255) NOT NULL,
    field_type VARCHAR(50)  NOT NULL,
    label      VARCHAR(255) NOT NULL,
    required   BOOLEAN      DEFAULT FALSE,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uix_tenant_fields (tenant_id, entity, field_name)
)
SQL;
    }
}
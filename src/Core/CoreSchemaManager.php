<?php

namespace EntityForge\Core;

use EntityForge\Database\Connection;
use PDO;

class CoreSchemaManager
{
    private PDO $pdo;

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
     * Define all core tables here
     */
    private function definitions(): array
    {
        return [
            $this->tenantsTable(),
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
}
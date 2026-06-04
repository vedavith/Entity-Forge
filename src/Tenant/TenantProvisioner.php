<?php

namespace EntityForge\Tenant;

use EntityForge\Database\Connection;
use EntityForge\Database\MigrationRunner;

class TenantProvisioner
{
    public function __construct(private array $config) {}

    public function create(string $tenantId): void
    {
        $dbConfig = $this->config['database'];
        $dbName = $dbConfig['database'] . '_' . $tenantId;

        // 1. Create DB
        $this->createDatabase($dbConfig, $dbName);

        // 2. Run migrations on tenant DB
        $tenantConfig = $dbConfig;
        $tenantConfig['database'] = $dbName;

        $connection = new Connection($tenantConfig);
        $runner = new MigrationRunner($connection);

        $runner->run('database/migrations');
    }

    private function createDatabase(array $config, string $dbName): void
    {
        $dsn = sprintf(
            '%s:host=%s;port=%s',
            $config['driver'],
            $config['host'],
            $config['port']
        );

        $pdo = new \PDO(
            $dsn,
            $config['username'],
            $config['password'],
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );

        $pdo->exec("CREATE DATABASE IF NOT EXISTS {$dbName}");
    }
}
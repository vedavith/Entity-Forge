<?php

namespace EntityForge\Tenant;

use EntityForge\Database\Connection;
use EntityForge\Database\MigrationRunner;

class TenantProvisioner
{
    /** @param array<string, mixed> $config */
    public function __construct(private array $config) {}

    public function create(string $tenantId): void
    {
        $dbConfig = $this->config['database'];
        $dbName = $dbConfig['database'] . '_' . $tenantId;

        $this->createDatabase($dbConfig, $dbName);

        $tenantConfig = $dbConfig;
        $tenantConfig['database'] = $dbName;

        try {
            $connection = new Connection($tenantConfig);
            $runner = new MigrationRunner($connection);
            $runner->run('database/migrations');
        } catch (\Throwable $e) {
            $this->dropDatabase($dbConfig, $dbName);
            throw $e;
        }
    }

    public function drop(string $tenantId): void
    {
        $dbConfig = $this->config['database'];
        $dbName = $dbConfig['database'] . '_' . $tenantId;
        $this->dropDatabase($dbConfig, $dbName);
    }

    /** @param array<string, mixed> $config */
    protected function getRootPdo(array $config): \PDO
    {
        $dsn = sprintf('%s:host=%s;port=%s', $config['driver'], $config['host'], $config['port']);
        return new \PDO(
            $dsn,
            $config['username'],
            $config['password'],
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
    }

    /** @param array<string, mixed> $config */
    private function createDatabase(array $config, string $dbName): void
    {
        $this->getRootPdo($config)->exec("CREATE DATABASE IF NOT EXISTS {$dbName}");
    }

    /** @param array<string, mixed> $config */
    private function dropDatabase(array $config, string $dbName): void
    {
        $this->getRootPdo($config)->exec("DROP DATABASE IF EXISTS {$dbName}");
    }
}
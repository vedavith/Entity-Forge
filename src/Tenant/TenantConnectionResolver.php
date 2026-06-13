<?php

namespace EntityForge\Tenant;

use EntityForge\Database\Connection;
use Exception;

class TenantConnectionResolver
{
    /** @var array<string, Connection> */
    private static array $connections = [];

    /**
     * @param array<string, mixed> $config
     * @throws Exception
     */
    public static function resolve(array $config): Connection
    {
        $strategy = $config['tenancy']['strategy'] ?? 'shared';

        if ($strategy === 'shared') {
            $key = 'shared:' . $config['database']['database'];
            return self::$connections[$key] ??= new Connection($config['database']);
        }

        if ($strategy === 'database') {
            $tenantId = TenantContext::getTenantId();

            if (!$tenantId) {
                throw new Exception("Tenant ID not set");
            }

            $dbConfig = $config['database'];
            $dbConfig['database'] = $dbConfig['database'] . '_' . $tenantId;

            $key = 'database:' . $dbConfig['database'];
            return self::$connections[$key] ??= new Connection($dbConfig);
        }

        throw new Exception("Unsupported tenancy strategy");
    }

    public static function flush(): void
    {
        self::$connections = [];
    }
}
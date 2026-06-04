<?php

namespace EntityForge\Tenant;

use EntityForge\Database\Connection;
use Exception;

class TenantConnectionResolver
{
    /**
     * @throws Exception
     */
    public static function resolve(array $config): Connection
    {
        $strategy = $config['tenancy']['strategy'] ?? 'shared';

        if ($strategy === 'shared') {
            return new Connection($config['database']);
        }

        if ($strategy === 'database') {
            $tenantId = \EntityForge\Tenant\TenantContext::getTenantId();

            if (!$tenantId) {
                throw new Exception("Tenant ID not set");
            }

            $dbConfig = $config['database'];
            $dbConfig['database'] = $dbConfig['database'] . '_' . $tenantId;

            return new Connection($dbConfig);
        }

        throw new Exception("Unsupported tenancy strategy");
    }
}
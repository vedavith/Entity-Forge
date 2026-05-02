<?php

namespace EntityForge\Tenant;

use Exception;

class TenantGuard
{
    /**
     * @throws Exception
     */
    public static function ensureTenant(): void
    {
        if (!TenantContext::hasTenantId()) {
            throw new Exception('Tenant ID is not set');
        }
    }
}
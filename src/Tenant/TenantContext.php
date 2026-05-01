<?php

namespace EntityForge\Tenant;

use Exception;

class TenantContext
{
    private static ?string $tenantId = null;

    public static function setTenantId(string $tenantId): void
    {
        self::$tenantId = $tenantId;
    }

    /**
     * @throws Exception
     */
    public static function getTenantId(): ?string
    {
        if (self::$tenantId === null) {
            throw new Exception('Tenant ID is not set');
        }

        return self::$tenantId;
    }

    public static function hasTenantId(): bool
    {
        return self::$tenantId !== null;
    }

    public static function clearTenantId(): void
    {
        self::$tenantId = null;
    }
}
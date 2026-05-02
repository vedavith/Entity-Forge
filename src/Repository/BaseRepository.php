<?php

namespace EntityForge\Repository;

use EntityForge\Tenant\TenantContext;
use EntityForge\Tenant\TenantGuard;
use Exception;
abstract class BaseRepository
{
    /**
     * @throws Exception
     */
    protected function getTenantId(): string
    {
        TenantGuard::ensureTenant();
        return TenantContext::getTenantId();
    }

    /**
     * @throws Exception
     */
    protected function applyTenantScope(array $data): array
    {
        $data['tenant_id'] = $this->getTenantId();
        return $data;
    }
}
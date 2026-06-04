<?php

namespace Tests\Tenant;

use EntityForge\Tenant\TenantContext;
use EntityForge\Tenant\TenantGuard;
use Exception;
use PHPUnit\Framework\TestCase;

class TenantGuardTest extends TestCase
{
    protected function setUp(): void
    {
        TenantContext::clear();
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
    }

    public function test_ensure_tenant_passes_when_tenant_is_set(): void
    {
        TenantContext::setTenantId('acme');

        TenantGuard::ensureTenant();
        $this->assertTrue(true);
    }

    public function test_ensure_tenant_throws_when_tenant_not_set(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Tenant ID is not set');

        TenantGuard::ensureTenant();
    }
}

<?php

namespace Tests\Tenant;

use EntityForge\Tenant\TenantContext;
use Exception;
use PHPUnit\Framework\TestCase;

class TenantContextTest extends TestCase
{
    protected function setUp(): void
    {
        TenantContext::clear();
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
    }

    public function test_set_and_get_tenant_id(): void
    {
        TenantContext::setTenantId('acme');

        $this->assertSame('acme', TenantContext::getTenantId());
    }

    public function test_has_tenant_id_false_when_not_set(): void
    {
        $this->assertFalse(TenantContext::hasTenantId());
    }

    public function test_has_tenant_id_true_after_set(): void
    {
        TenantContext::setTenantId('acme');

        $this->assertTrue(TenantContext::hasTenantId());
    }

    public function test_get_tenant_id_throws_when_not_set(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Tenant ID is not set');

        TenantContext::getTenantId();
    }

    public function test_clear_resets_tenant_id(): void
    {
        TenantContext::setTenantId('acme');
        TenantContext::clear();

        $this->assertFalse(TenantContext::hasTenantId());
    }

    public function test_set_throws_when_tenant_already_set(): void
    {
        TenantContext::setTenantId('first');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/RequestLifecycle::begin/');

        TenantContext::setTenantId('second');
    }

    public function test_set_succeeds_after_clear(): void
    {
        TenantContext::setTenantId('first');
        TenantContext::clear();
        TenantContext::setTenantId('second');

        $this->assertSame('second', TenantContext::getTenantId());
    }
}

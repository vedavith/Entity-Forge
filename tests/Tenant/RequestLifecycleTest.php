<?php

namespace Tests\Tenant;

use EntityForge\Tenant\RequestLifecycle;
use EntityForge\Tenant\TenantConnectionResolver;
use EntityForge\Tenant\TenantContext;
use PHPUnit\Framework\TestCase;

class RequestLifecycleTest extends TestCase
{
    protected function setUp(): void
    {
        TenantContext::clear();
        TenantConnectionResolver::flush();
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        TenantConnectionResolver::flush();
    }

    public function test_begin_clears_tenant_context(): void
    {
        TenantContext::setTenantId('acme');

        RequestLifecycle::begin();

        $this->assertFalse(TenantContext::hasTenantId());
    }

    public function test_end_clears_tenant_context(): void
    {
        TenantContext::setTenantId('acme');

        RequestLifecycle::end();

        $this->assertFalse(TenantContext::hasTenantId());
    }

    public function test_begin_is_safe_when_context_already_empty(): void
    {
        RequestLifecycle::begin();

        $this->assertFalse(TenantContext::hasTenantId());
    }

    public function test_end_is_safe_when_context_already_empty(): void
    {
        RequestLifecycle::end();

        $this->assertFalse(TenantContext::hasTenantId());
    }
}

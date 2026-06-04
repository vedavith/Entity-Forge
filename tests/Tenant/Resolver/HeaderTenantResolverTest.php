<?php

namespace Tests\Tenant\Resolver;

use EntityForge\Tenant\Resolver\HeaderTenantResolver;
use Exception;
use PHPUnit\Framework\TestCase;

class HeaderTenantResolverTest extends TestCase
{
    public function test_resolves_tenant_from_default_header(): void
    {
        $resolver = new HeaderTenantResolver();

        $tenantId = $resolver->resolve(['headers' => ['X-Tenant-ID' => 'acme']]);

        $this->assertSame('acme', $tenantId);
    }

    public function test_resolves_tenant_from_custom_header(): void
    {
        $resolver = new HeaderTenantResolver('X-Custom-Tenant');

        $tenantId = $resolver->resolve(['headers' => ['X-Custom-Tenant' => 'bigcorp']]);

        $this->assertSame('bigcorp', $tenantId);
    }

    public function test_throws_when_header_missing(): void
    {
        $resolver = new HeaderTenantResolver();

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/Tenant header '.*' not found/");

        $resolver->resolve(['headers' => []]);
    }

    public function test_throws_when_headers_key_absent(): void
    {
        $resolver = new HeaderTenantResolver();

        $this->expectException(Exception::class);

        $resolver->resolve([]);
    }

    public function test_throws_when_wrong_header_provided(): void
    {
        $resolver = new HeaderTenantResolver('X-Tenant-ID');

        $this->expectException(Exception::class);

        $resolver->resolve(['headers' => ['Authorization' => 'Bearer token']]);
    }
}

<?php

namespace Tests\Tenant;

use EntityForge\Tenant\Resolver\HeaderTenantResolver;
use EntityForge\Tenant\Resolver\SubdomainTenantResolver;
use EntityForge\Tenant\TenantResolverFactory;
use Exception;
use PHPUnit\Framework\TestCase;

class TenantResolverFactoryTest extends TestCase
{
    public function test_creates_header_resolver_by_default(): void
    {
        $resolver = TenantResolverFactory::create(['tenancy' => ['resolver' => 'header']]);

        $this->assertInstanceOf(HeaderTenantResolver::class, $resolver);
    }

    public function test_defaults_to_header_resolver_when_resolver_key_absent(): void
    {
        $resolver = TenantResolverFactory::create(['tenancy' => []]);

        $this->assertInstanceOf(HeaderTenantResolver::class, $resolver);
    }

    public function test_header_resolver_uses_configured_header_key(): void
    {
        $resolver = TenantResolverFactory::create([
            'tenancy' => ['resolver' => 'header', 'header_key' => 'X-Custom-Tenant'],
        ]);

        $tenantId = $resolver->resolve(['headers' => ['X-Custom-Tenant' => 'myorg']]);
        $this->assertSame('myorg', $tenantId);
    }

    public function test_header_resolver_uses_default_header_when_key_absent(): void
    {
        $resolver = TenantResolverFactory::create(['tenancy' => ['resolver' => 'header']]);

        $tenantId = $resolver->resolve(['headers' => ['X-Tenant-ID' => 'org1']]);
        $this->assertSame('org1', $tenantId);
    }

    public function test_creates_subdomain_resolver(): void
    {
        $resolver = TenantResolverFactory::create(['tenancy' => ['resolver' => 'subdomain']]);

        $this->assertInstanceOf(SubdomainTenantResolver::class, $resolver);
    }

    public function test_subdomain_resolver_resolves_host(): void
    {
        $resolver = TenantResolverFactory::create(['tenancy' => ['resolver' => 'subdomain']]);

        $tenantId = $resolver->resolve(['host' => 'acme.example.com']);
        $this->assertSame('acme', $tenantId);
    }

    public function test_throws_for_unsupported_resolver_type(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Unsupported tenant resolver type/');

        TenantResolverFactory::create(['tenancy' => ['resolver' => 'jwt']]);
    }
}

<?php

namespace Tests\Tenant\Resolver;

use EntityForge\Tenant\Resolver\SubdomainTenantResolver;
use Exception;
use PHPUnit\Framework\TestCase;

class SubdomainTenantResolverTest extends TestCase
{
    public function test_resolves_subdomain_from_host(): void
    {
        $resolver = new SubdomainTenantResolver();
        $tenantId = $resolver->resolve(['host' => 'acme.example.com']);
        $this->assertSame('acme', $tenantId);
    }

    public function test_resolves_subdomain_from_server_http_host(): void
    {
        $resolver = new SubdomainTenantResolver();
        $tenantId = $resolver->resolve(['server' => ['HTTP_HOST' => 'tenant1.myapp.io']]);
        $this->assertSame('tenant1', $tenantId);
    }

    public function test_strips_port_from_host(): void
    {
        $resolver = new SubdomainTenantResolver();
        $tenantId = $resolver->resolve(['host' => 'acme.example.com:8080']);
        $this->assertSame('acme', $tenantId);
    }

    public function test_throws_when_host_missing(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/HTTP_HOST not found/');

        (new SubdomainTenantResolver())->resolve([]);
    }

    public function test_throws_when_no_subdomain_present(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Cannot extract subdomain/');

        (new SubdomainTenantResolver())->resolve(['host' => 'example.com']);
    }

    public function test_host_key_takes_precedence_over_server(): void
    {
        $resolver = new SubdomainTenantResolver();
        $tenantId = $resolver->resolve([
            'host' => 'primary.example.com',
            'server' => ['HTTP_HOST' => 'other.example.com'],
        ]);
        $this->assertSame('primary', $tenantId);
    }

    public function test_min_parts_2_resolves_two_part_host(): void
    {
        $resolver = new SubdomainTenantResolver(subdomainDepth: 0, minParts: 2);
        $tenantId = $resolver->resolve(['host' => 'acme.io']);
        $this->assertSame('acme', $tenantId);
    }

    public function test_min_parts_2_still_throws_for_single_part_host(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Cannot extract subdomain/');

        (new SubdomainTenantResolver(subdomainDepth: 0, minParts: 2))->resolve(['host' => 'localhost']);
    }

    public function test_default_min_parts_3_still_rejects_two_part_host(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Cannot extract subdomain/');

        (new SubdomainTenantResolver())->resolve(['host' => 'acme.io']);
    }

    public function test_factory_passes_subdomain_min_parts_from_config(): void
    {
        $config = [
            'tenancy' => [
                'resolver'            => 'subdomain',
                'subdomain_min_parts' => 2,
            ],
        ];

        $resolver = \EntityForge\Tenant\TenantResolverFactory::create($config);
        $tenantId = $resolver->resolve(['host' => 'acme.io']);

        $this->assertSame('acme', $tenantId);
    }
}

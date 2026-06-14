<?php

namespace EntityForge\Tenant;

use EntityForge\Tenant\Resolver\HeaderTenantResolver;
use EntityForge\Tenant\Resolver\JwtTenantResolver;
use EntityForge\Tenant\Resolver\SessionTenantResolver;
use EntityForge\Tenant\Resolver\SubdomainTenantResolver;
use Exception;

class TenantResolverFactory
{
    /**
     * @param array<string, mixed> $config
     * @throws Exception
     */
    public static function create(array $config): TenantResolverInterface
    {
        $resolverType = $config['tenancy']['resolver'] ?? 'header';
        return match ($resolverType) {
            'header' => new HeaderTenantResolver(
                $config['tenancy']['header_key'] ?? 'X-Tenant-ID'
            ),
            'subdomain' => new SubdomainTenantResolver(
                (int) ($config['tenancy']['subdomain_depth'] ?? 0),
                (int) ($config['tenancy']['subdomain_min_parts'] ?? 3)
            ),
            'jwt' => new JwtTenantResolver(
                $config['tenancy']['jwt_public_key'] ?? '',
                $config['tenancy']['jwt_algorithm']  ?? 'RS256',
                $config['tenancy']['jwt_tenant_claim'] ?? 'tenant_id'
            ),
            'session' => new SessionTenantResolver(
                $config['tenancy']['session_key'] ?? 'tenant_id'
            ),
            default => throw new Exception("Unsupported tenant resolver type: {$resolverType}"),
        };
    }
}
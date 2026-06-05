<?php

namespace EntityForge\Tenant;

use EntityForge\Tenant\Resolver\HeaderTenantResolver;
use EntityForge\Tenant\Resolver\SubdomainTenantResolver;
use Exception;

class TenantResolverFactory
{
    /**
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
                (int) ($config['tenancy']['subdomain_depth'] ?? 0)
            ),
            default => throw new Exception("Unsupported tenant resolver type: {$resolverType}"),
        };
    }
}
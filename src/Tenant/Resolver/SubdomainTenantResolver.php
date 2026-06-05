<?php

namespace EntityForge\Tenant\Resolver;

use EntityForge\Tenant\TenantResolverInterface;
use Exception;

class SubdomainTenantResolver implements TenantResolverInterface
{
    public function __construct(
        private int $subdomainDepth = 0,
        private int $minParts = 3
    ) {}

    /**
     * @throws Exception
     */
    public function resolve(array $context): string
    {
        $host = $context['host']
            ?? $context['server']['HTTP_HOST']
            ?? null;

        if (!$host) {
            throw new Exception("HTTP_HOST not found in context.");
        }

        // Strip port if present (e.g. "acme.example.com:8080")
        $host = explode(':', $host)[0];

        $parts = explode('.', $host);

        if (count($parts) < $this->subdomainDepth + $this->minParts) {
            throw new Exception("Cannot extract subdomain from host: {$host}");
        }

        $subdomain = $parts[$this->subdomainDepth];

        if ($subdomain === '') {
            throw new Exception("Empty subdomain in host: {$host}");
        }

        return $subdomain;
    }
}

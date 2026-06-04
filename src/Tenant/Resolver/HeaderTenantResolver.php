<?php

namespace EntityForge\Tenant\Resolver;

use EntityForge\Tenant\TenantResolverInterface;
use Exception;

class HeaderTenantResolver implements TenantResolverInterface
{

    private string $headerKey;

    public function __construct(string $headerKey = 'X-Tenant-ID')
    {
        $this->headerKey = $headerKey;
    }

    /**
     * @throws Exception
     */
    public function resolve(array $context): string
    {
        if (!isset($context['headers'][$this->headerKey])) {
            throw new Exception("Tenant header '{$this->headerKey}' not found.");
        }

        return $context['headers'][$this->headerKey];
    }
}
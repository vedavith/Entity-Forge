<?php

namespace EntityForge\Tenant;

interface TenantResolverInterface
{
    /** @param array<string, mixed> $context */
    public function resolve(array $context): string;
}
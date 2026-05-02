<?php

namespace EntityForge\Tenant;

interface TenantResolverInterface
{
    public function resolve(array $context): string;
}
<?php

namespace EntityForge\Tenant;

/**
 * Manages TenantContext state across request boundaries.
 *
 * In PHP-FPM, static state is reset per-process. In long-lived workers
 * (Swoole, RoadRunner, Octane), the same process handles many requests —
 * call begin() at the start and end() at the end of each request loop to
 * prevent tenant state from leaking between requests.
 */
class RequestLifecycle
{
    public static function begin(): void
    {
        TenantContext::clear();
        TenantConnectionResolver::flush();
    }

    public static function end(): void
    {
        TenantContext::clear();
        TenantConnectionResolver::flush();
    }
}

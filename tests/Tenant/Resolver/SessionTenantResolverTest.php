<?php

namespace Tests\Tenant\Resolver;

use EntityForge\Tenant\Resolver\SessionTenantResolver;
use Exception;
use PHPUnit\Framework\TestCase;

class SessionTenantResolverTest extends TestCase
{
    public function test_resolves_tenant_from_context_session(): void
    {
        $resolver = new SessionTenantResolver();

        $tenantId = $resolver->resolve(['session' => ['tenant_id' => 'acme']]);

        $this->assertSame('acme', $tenantId);
    }

    public function test_resolves_tenant_from_custom_session_key(): void
    {
        $resolver = new SessionTenantResolver('current_tenant');

        $tenantId = $resolver->resolve(['session' => ['current_tenant' => 'bigcorp']]);

        $this->assertSame('bigcorp', $tenantId);
    }

    public function test_throws_when_session_key_missing(): void
    {
        $resolver = new SessionTenantResolver();

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/Tenant key 'tenant_id' not found in session/");

        $resolver->resolve(['session' => []]);
    }

    public function test_throws_when_session_value_is_empty_string(): void
    {
        $resolver = new SessionTenantResolver();

        $this->expectException(Exception::class);

        $resolver->resolve(['session' => ['tenant_id' => '']]);
    }

    public function test_casts_non_string_session_value_to_string(): void
    {
        $resolver = new SessionTenantResolver();

        $tenantId = $resolver->resolve(['session' => ['tenant_id' => 42]]);

        $this->assertSame('42', $tenantId);
    }

    // ── $_SESSION fallback path ─────────────────────────────────────────────────

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_resolves_tenant_from_global_session(): void
    {
        session_start();
        $_SESSION['tenant_id'] = 'from-session';

        $resolver = new SessionTenantResolver();
        $tenantId = $resolver->resolve([]);

        $this->assertSame('from-session', $tenantId);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_throws_when_global_session_key_missing(): void
    {
        $resolver = new SessionTenantResolver();
        $_SESSION = [];

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/Tenant key 'tenant_id' not found in session/");

        $resolver->resolve([]);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_throws_when_global_session_value_is_empty_string(): void
    {
        $resolver = new SessionTenantResolver();
        $_SESSION['tenant_id'] = '';

        $this->expectException(Exception::class);

        $resolver->resolve([]);
    }
}

<?php

namespace EntityForge\Tenant\Resolver;

use EntityForge\Tenant\TenantResolverInterface;
use Exception;

class SessionTenantResolver implements TenantResolverInterface
{
    public function __construct(private string $sessionKey = 'tenant_id') {}

    /**
     * @param array<string, mixed> $context
     * @throws Exception
     */
    public function resolve(array $context): string
    {
        // Accept session data injected via context (testable) or fall back to $_SESSION
        if (isset($context['session'])) {
            /** @var array<string, mixed> $session */
            $session = $context['session'];
            if (!isset($session[$this->sessionKey]) || $session[$this->sessionKey] === '') {
                throw new Exception("Tenant key '{$this->sessionKey}' not found in session.");
            }
            return (string) $session[$this->sessionKey];
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION[$this->sessionKey]) || $_SESSION[$this->sessionKey] === '') {
            throw new Exception("Tenant key '{$this->sessionKey}' not found in session.");
        }

        return (string) $_SESSION[$this->sessionKey];
    }
}

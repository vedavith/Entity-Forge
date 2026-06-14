<?php

namespace EntityForge\Tenant\Resolver;

use EntityForge\Tenant\TenantResolverInterface;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtTenantResolver implements TenantResolverInterface
{
    public function __construct(
        private readonly string $publicKey,
        private readonly string $algorithm = 'RS256',
        private readonly string $tenantClaim = 'tenant_id'
    ) {}

    /**
     * @param array<string, mixed> $context
     * @throws Exception
     */
    public function resolve(array $context): string
    {
        $header = $context['headers']['Authorization']
            ?? $context['headers']['authorization']
            ?? null;

        if ($header === null) {
            throw new Exception('Authorization header missing.');
        }

        if (!str_starts_with($header, 'Bearer ')) {
            throw new Exception('Authorization header must use Bearer scheme.');
        }

        $token = substr($header, 7);

        try {
            $decoded = JWT::decode($token, new Key($this->publicKey, $this->algorithm));
        } catch (\Throwable $e) {
            throw new Exception('Invalid JWT: ' . $e->getMessage(), 0, $e);
        }

        $payload = (array) $decoded;

        if (empty($payload[$this->tenantClaim])) {
            throw new Exception("JWT is missing the '{$this->tenantClaim}' claim.");
        }

        return (string) $payload[$this->tenantClaim];
    }
}

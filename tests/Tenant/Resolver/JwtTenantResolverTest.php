<?php

namespace Tests\Tenant\Resolver;

use EntityForge\Tenant\Resolver\JwtTenantResolver;
use Firebase\JWT\JWT;
use PHPUnit\Framework\TestCase;

class JwtTenantResolverTest extends TestCase
{
    private string $privateKey = <<<EOK
    -----BEGIN PRIVATE KEY-----
    MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQDL6Ql9m/YknqZp
    dphmuKLJ0KEVEI057bkpuAMhe0JRbxO+M7ckH4urqlrxDpPtHh5ojeS7JTg6GhKy
    bCc5Pw66MBeI8KHxWWfWVYzThh8ot/omHSTOsIj+xbAqcOBHSoJjgzE55oa3epts
    9JUuxsKMhLkKgA/CHEMoLt0DmrYB1mm3goeSNgOiWIevB8ivXYNUhsq/Md7D56m3
    IpDW0tJiD5NI9blRaj3WosEL3IDoTPaly1s/FXX4x3sgCJSI8n9RQhMbmCMkZX3Z
    FS5g8fs1nzsPo/8ianqOdUVrR2rZFpuv8hZEv0YE6jkJcoWef7F37TGUdjb9+zp9
    Rg5KsYLBAgMBAAECggEAAUbOompop6Z9JVfDgbU482WTFe3s2S8B1VdsWnOhNLIY
    lJUPCpjXoOdxyIY1IzwTSZbtWbmv9pVxm+EjlFhBBEpR2/i/sjrAUCjQEqq0I39l
    Xfz7FBIj/Eg/xZWVKIpvV2xdKc5QV8RFZMMTwrxpUlNX3mrIF54Mj3E2DDpcJdjz
    4qzciP3cxf+muGYptL6iB9yIo635MsqOZiKDMXZb99m/19Zh2B04WQGSPILpGoCd
    bQcQCjWo7AkU2cXXbMLtUE0AryQUiuxBRKlmkvtpsVyZx5WItMxCjxSdYZzhqp23
    8fAV6DIz0QOyde3lzi5WqDjzZrqJhWkDIJw2XsOeHQKBgQD9mHDmqtFeLLlAXh0N
    6fV3+MzagzF4sHRBI/3CghDLg4ta9UF8sw/ftKjryzZ7lItM6BVJYj7P8GFVsbH8
    eIpne0f3VTWqnITOY36i0Gc0tJ8fnd6jg8Cj1ukdP2DRa7KqMzystVFXRqVZtoxY
    D3Q4GQJVJYDnqrFKatRvpdbcbQKBgQDN1/5x5UusB9lJd+0qy7s+ElJMJZpm1PcN
    UO92cECxPJDJMI5eKjdJocNHwqXUjQtxndpdVnadIZl2Oaqe2StPFUAYbpQq0aS2
    drkfgss6m5ZQShx38WaYSBiU3Krn6UTmwqb3KNAGJ3X6d8raZlUO/vjDVZI5CZ0b
    IQkV1ovjJQKBgAVPF638jX2HOimazsjnyPfGAaPhczuvlf93HWzhBDD+hABXehN+
    PCrWwWKOUomrxm7JvQhYQjBgO+lrWuqKK3uXHR6UbhcwR6d6dcA61K5JybsDtxF9
    RJ8pdJ/kH8bClm7xu6dx9E37cKK8K5v8VtaFz4Kw0k4HSMbiDah4tFLRAoGAFWB3
    BjD/2M+/2wdfU/Bwc5PHhCzrif5X4cQj+jLSJRXbG2m1f0X3E+h+tTcbraUwKQ5x
    nPvbuZnBrCt08qYu/zl/vInPTVsUNfbCZulYXa/GvnPT3Qju1KW4F82K2ia5hxVz
    7XsJj3oNoINMR74U20fTYcXDN7Ut4aFepivvvxkCgYEAzbiSSw4WBqAMYfX2Kffl
    kZt5y7LfQxku3CEvTPJ1+Icsky7E1M73oLXM2zvYbKmj65vex5mPIVKjyUHQ57o3
    614RSgayOxw8r9ddtQnU+8W4pBrfnaLhydzTs6POFxO1I1DcCArubOLtsP+XvCkk
    69N6T49r9xYx/6PuKpCE48g=
    -----END PRIVATE KEY-----
    EOK;

    private string $publicKey = <<<EOK
    -----BEGIN PUBLIC KEY-----
    MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAy+kJfZv2JJ6maXaYZrii
    ydChFRCNOe25KbgDIXtCUW8TvjO3JB+Lq6pa8Q6T7R4eaI3kuyU4OhoSsmwnOT8O
    ujAXiPCh8Vln1lWM04YfKLf6Jh0kzrCI/sWwKnDgR0qCY4MxOeaGt3qbbPSVLsbC
    jIS5CoAPwhxDKC7dA5q2AdZpt4KHkjYDoliHrwfIr12DVIbKvzHew+eptyKQ1tLS
    Yg+TSPW5UWo91qLBC9yA6Ez2pctbPxV1+Md7IAiUiPJ/UUITG5gjJGV92RUuYPH7
    NZ87D6P/Imp6jnVFa0dq2Rabr/IWRL9GBOo5CXKFnn+xd+0xlHY2/fs6fUYOSrGC
    wQIDAQAB
    -----END PUBLIC KEY-----
    EOK;

    private function makeToken(array $payload): string
    {
        return JWT::encode($payload, $this->privateKey, 'RS256');
    }

    private function resolver(?string $claim = null): JwtTenantResolver
    {
        return $claim
            ? new JwtTenantResolver($this->publicKey, 'RS256', $claim)
            : new JwtTenantResolver($this->publicKey);
    }

    public function test_resolves_tenant_id_from_bearer_token(): void
    {
        $token = $this->makeToken(['tenant_id' => 'acme', 'exp' => time() + 60]);

        $tenantId = $this->resolver()->resolve([
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertSame('acme', $tenantId);
    }

    public function test_resolves_custom_claim(): void
    {
        $token = $this->makeToken(['org' => 'corp', 'exp' => time() + 60]);

        $tenantId = $this->resolver('org')->resolve([
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertSame('corp', $tenantId);
    }

    public function test_resolves_case_insensitive_authorization_header(): void
    {
        $token = $this->makeToken(['tenant_id' => 'acme', 'exp' => time() + 60]);

        $tenantId = $this->resolver()->resolve([
            'headers' => ['authorization' => 'Bearer ' . $token],
        ]);

        $this->assertSame('acme', $tenantId);
    }

    public function test_throws_when_authorization_header_missing(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Authorization header missing/');

        $this->resolver()->resolve(['headers' => []]);
    }

    public function test_throws_when_bearer_scheme_absent(): void
    {
        $token = $this->makeToken(['tenant_id' => 'acme', 'exp' => time() + 60]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Bearer scheme/');

        $this->resolver()->resolve(['headers' => ['Authorization' => $token]]);
    }

    public function test_throws_when_token_is_invalid(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Invalid JWT/');

        $this->resolver()->resolve(['headers' => ['Authorization' => 'Bearer not.a.jwt']]);
    }

    public function test_throws_when_tenant_claim_missing_from_payload(): void
    {
        $token = $this->makeToken(['sub' => 'user_1', 'exp' => time() + 60]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches("/tenant_id/");

        $this->resolver()->resolve(['headers' => ['Authorization' => 'Bearer ' . $token]]);
    }

    public function test_throws_when_token_is_expired(): void
    {
        $token = $this->makeToken(['tenant_id' => 'acme', 'exp' => time() - 10]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Invalid JWT/');

        $this->resolver()->resolve(['headers' => ['Authorization' => 'Bearer ' . $token]]);
    }
}

<?php

namespace Tests\Tenant;

use EntityForge\Tenant\TenantConnectionResolver;
use EntityForge\Tenant\TenantContext;
use Exception;
use PHPUnit\Framework\TestCase;

class TenantConnectionResolverTest extends TestCase
{
    protected function setUp(): void
    {
        TenantContext::clear();
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
    }

    public function test_throws_for_unsupported_strategy(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unsupported tenancy strategy');

        TenantConnectionResolver::resolve([
            'tenancy'  => ['strategy' => 'schema'],
            'database' => [],
        ]);
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    public function test_shared_strategy_returns_connection(): void
    {
        $conn = \Mockery::mock('overload:' . \EntityForge\Database\Connection::class);

        $result = \EntityForge\Tenant\TenantConnectionResolver::resolve([
            'tenancy'  => ['strategy' => 'shared'],
            'database' => [
                'driver' => 'mysql', 'host' => 'localhost', 'port' => 3306,
                'database' => 'app', 'username' => 'root', 'password' => 'root',
            ],
        ]);

        $this->assertInstanceOf(\EntityForge\Database\Connection::class, $result);
    }

    public function test_database_strategy_throws_when_tenant_not_set(): void
    {
        $this->expectException(Exception::class);

        TenantConnectionResolver::resolve([
            'tenancy'  => ['strategy' => 'database'],
            'database' => [
                'driver'   => 'mysql',
                'host'     => 'localhost',
                'port'     => 3306,
                'database' => 'app',
                'username' => 'root',
                'password' => 'root',
            ],
        ]);
    }
}

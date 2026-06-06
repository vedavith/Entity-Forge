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

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    public function test_database_strategy_returns_connection_for_tenant(): void
    {
        TenantContext::setTenantId('acme');

        \Mockery::mock('overload:' . \EntityForge\Database\Connection::class);

        $result = TenantConnectionResolver::resolve([
            'tenancy'  => ['strategy' => 'database'],
            'database' => [
                'driver' => 'mysql', 'host' => 'localhost', 'port' => 3306,
                'database' => 'app', 'username' => 'root', 'password' => 'root',
            ],
        ]);

        $this->assertInstanceOf(\EntityForge\Database\Connection::class, $result);

        \Mockery::close();
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    public function test_shared_strategy_caches_connection(): void
    {
        \Mockery::mock('overload:' . \EntityForge\Database\Connection::class);

        $config = [
            'tenancy'  => ['strategy' => 'shared'],
            'database' => [
                'driver' => 'mysql', 'host' => 'localhost', 'port' => 3306,
                'database' => 'app', 'username' => 'root', 'password' => 'root',
            ],
        ];

        $a = TenantConnectionResolver::resolve($config);
        $b = TenantConnectionResolver::resolve($config);

        $this->assertSame($a, $b);

        \Mockery::close();
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    public function test_flush_clears_connection_cache(): void
    {
        \Mockery::mock('overload:' . \EntityForge\Database\Connection::class);

        $config = [
            'tenancy'  => ['strategy' => 'shared'],
            'database' => [
                'driver' => 'mysql', 'host' => 'localhost', 'port' => 3306,
                'database' => 'app', 'username' => 'root', 'password' => 'root',
            ],
        ];

        $a = TenantConnectionResolver::resolve($config);
        TenantConnectionResolver::flush();
        $b = TenantConnectionResolver::resolve($config);

        $this->assertNotSame($a, $b);

        \Mockery::close();
    }
}

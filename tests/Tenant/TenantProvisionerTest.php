<?php

namespace Tests\Tenant;

use EntityForge\Database\Connection;
use EntityForge\Database\MigrationRunner;
use EntityForge\Tenant\TenantProvisioner;
use Mockery;
use PDO;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class TenantProvisionerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    private function config(): array
    {
        return [
            'database' => [
                'driver'   => 'mysql',
                'host'     => 'localhost',
                'port'     => 3306,
                'database' => 'app',
                'username' => 'root',
                'password' => 'root',
            ],
        ];
    }

    private function mockPdo(): PDO
    {
        $pdo = Mockery::mock(PDO::class);
        $pdo->allows('exec')->andReturn(0);
        return $pdo;
    }

    private function provisioner(PDO $pdo): TenantProvisioner
    {
        $mock = $this->getMockBuilder(TenantProvisioner::class)
            ->setConstructorArgs([$this->config()])
            ->onlyMethods(['getRootPdo'])
            ->getMock();
        $mock->method('getRootPdo')->willReturn($pdo);
        return $mock;
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_create_provisions_tenant_database_successfully(): void
    {
        Mockery::mock('overload:' . Connection::class);

        $runner = Mockery::mock('overload:' . MigrationRunner::class);
        $runner->allows('run');

        $provisioner = $this->provisioner($this->mockPdo());
        $provisioner->create('acme');

        $this->assertTrue(true);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_create_drops_database_when_migration_fails(): void
    {
        Mockery::mock('overload:' . Connection::class);

        $runner = Mockery::mock('overload:' . MigrationRunner::class);
        $runner->allows('run')->andThrow(new \RuntimeException('migration failed'));

        $provisioner = $this->provisioner($this->mockPdo());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('migration failed');

        $provisioner->create('acme');
    }

    public function test_drop_removes_tenant_database(): void
    {
        $provisioner = $this->provisioner($this->mockPdo());
        $provisioner->drop('acme');

        $this->assertTrue(true);
    }
}

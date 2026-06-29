<?php

namespace Tests\Core;

use EntityForge\Core\CoreSchemaManager;
use EntityForge\Database\Connection;
use Mockery;
use Mockery\MockInterface;
use PDO;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

class CoreSchemaManagerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_ensure_executes_tenants_table_ddl(): void
    {
        $pdo = Mockery::mock(PDO::class);
        $pdo->allows('exec')
            ->with(Mockery::pattern('/CREATE TABLE IF NOT EXISTS/'))
            ->andReturn(0);

        /** @var MockInterface&Connection $conn */
        $conn = Mockery::mock('overload:' . Connection::class);
        $conn->allows('getPdo')->andReturn($pdo);

        $manager = new CoreSchemaManager([
            'database' => [
                'driver'   => 'mysql',
                'host'     => 'localhost',
                'port'     => 3306,
                'database' => 'app',
                'username' => 'root',
                'password' => 'root',
            ],
        ]);

        $manager->ensure();

        $this->assertTrue(true);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_ensure_ddl_contains_tenant_id_column(): void
    {
        $captured = [];

        $pdo = Mockery::mock(PDO::class);
        $pdo->allows('exec')
            ->with(Mockery::on(function (string $sql) use (&$captured) {
                $captured[] = $sql;
                return true;
            }))
            ->andReturn(0);

        /** @var MockInterface&Connection $conn */
        $conn = Mockery::mock('overload:' . Connection::class);
        $conn->allows('getPdo')->andReturn($pdo);

        $manager = new CoreSchemaManager([
            'database' => [
                'driver' => 'mysql', 'host' => 'localhost', 'port' => 3306,
                'database' => 'app', 'username' => 'root', 'password' => 'root',
            ],
        ]);

        $manager->ensure();

        $this->assertNotEmpty($captured);
        $this->assertStringContainsString('tenant_id', $captured[0]);
        $this->assertStringContainsString('tenants', $captured[0]);
    }
}

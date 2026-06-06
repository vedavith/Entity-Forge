<?php

namespace Tests\Console;

use EntityForge\Console\RollbackCommand;
use EntityForge\Core\Application;
use EntityForge\Database\Connection;
use EntityForge\Database\MigrationRunner;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class RollbackCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    private function mockApp(): void
    {
        $config = [
            'tenancy'  => ['strategy' => 'shared', 'enabled' => false],
            'database' => [
                'driver' => 'mysql', 'host' => 'localhost', 'port' => 3306,
                'database' => 'app', 'username' => 'root', 'password' => 'root',
            ],
        ];

        $app = Mockery::mock('overload:' . Application::class);
        $app->allows('boot');
        $app->allows('getConfig')->andReturn($config);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_execute_rolls_back_successfully(): void
    {
        $this->mockApp();

        Mockery::mock('overload:' . Connection::class);

        $runner = Mockery::mock('overload:' . MigrationRunner::class);
        $runner->allows('rollback')->with('database/migrations', false)->once();

        $tester = new CommandTester(new RollbackCommand());
        $code   = $tester->execute([]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('successful', $tester->getDisplay());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_execute_dry_run_passes_flag_to_runner(): void
    {
        $this->mockApp();

        Mockery::mock('overload:' . Connection::class);

        $runner = Mockery::mock('overload:' . MigrationRunner::class);
        $runner->allows('rollback')->with('database/migrations', true)->once();

        $tester = new CommandTester(new RollbackCommand());
        $code   = $tester->execute(['--dry-run' => true]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('Dry run', $tester->getDisplay());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_execute_returns_failure_on_exception(): void
    {
        $this->mockApp();

        Mockery::mock('overload:' . Connection::class);

        $runner = Mockery::mock('overload:' . MigrationRunner::class);
        $runner->allows('rollback')->andThrow(new \Exception('rollback error'));

        $tester = new CommandTester(new RollbackCommand());
        $code   = $tester->execute([]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('rollback error', $tester->getDisplay());
    }
}
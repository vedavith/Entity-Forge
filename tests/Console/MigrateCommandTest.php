<?php

namespace Tests\Console;

use EntityForge\Console\MigrateCommand;
use EntityForge\Core\Application;
use EntityForge\Database\Connection;
use EntityForge\Database\MigrationRunner;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class MigrateCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    private function mockApp(array $extra = []): void
    {
        $config = array_merge([
            'tenancy'  => ['strategy' => 'shared', 'enabled' => false],
            'database' => [
                'driver' => 'mysql', 'host' => 'localhost', 'port' => 3306,
                'database' => 'app', 'username' => 'root', 'password' => 'root',
            ],
        ], $extra);

        $app = Mockery::mock('overload:' . Application::class);
        $app->allows('boot');
        $app->allows('getConfig')->andReturn($config);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_execute_runs_migrations_successfully(): void
    {
        $this->mockApp();

        Mockery::mock('overload:' . Connection::class);

        $runner = Mockery::mock('overload:' . MigrationRunner::class);
        $runner->allows('run')->with('database/migrations', false)->once();

        $tester = new CommandTester(new MigrateCommand());
        $code   = $tester->execute([]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('successfully', $tester->getDisplay());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_execute_dry_run_passes_flag_to_runner(): void
    {
        $this->mockApp();

        Mockery::mock('overload:' . Connection::class);

        $runner = Mockery::mock('overload:' . MigrationRunner::class);
        $runner->allows('run')->with('database/migrations', true)->once();

        $tester = new CommandTester(new MigrateCommand());
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
        $runner->allows('run')->andThrow(new \Exception('migration error'));

        $tester = new CommandTester(new MigrateCommand());
        $code   = $tester->execute([]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('migration error', $tester->getDisplay());
    }
}
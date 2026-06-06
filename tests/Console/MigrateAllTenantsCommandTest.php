<?php

namespace Tests\Console;

use EntityForge\Console\MigrateAllTenantsCommand;
use EntityForge\Core\Application;
use EntityForge\Database\Connection;
use EntityForge\Database\MigrationRunner;
use EntityForge\Tenant\TenantRepository;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class MigrateAllTenantsCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    private function mockApp(string $strategy = 'database'): void
    {
        $config = [
            'tenancy'  => ['strategy' => $strategy, 'enabled' => false],
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
    public function test_tenant_mode_migrates_single_tenant_successfully(): void
    {
        $this->mockApp();

        Mockery::mock('overload:' . Connection::class);

        $runner = Mockery::mock('overload:' . MigrationRunner::class);
        $runner->allows('run')->with('database/migrations', false)->once();

        $tester = new CommandTester(new MigrateAllTenantsCommand());
        $code   = $tester->execute(['--tenant' => 'acme']);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('acme', $tester->getDisplay());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_tenant_mode_dry_run_passes_flag_to_runner(): void
    {
        $this->mockApp();

        Mockery::mock('overload:' . Connection::class);

        $runner = Mockery::mock('overload:' . MigrationRunner::class);
        $runner->allows('run')->with('database/migrations', true)->once();

        $tester = new CommandTester(new MigrateAllTenantsCommand());
        $code   = $tester->execute(['--tenant' => 'acme', '--dry-run' => true]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('Dry run', $tester->getDisplay());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_tenant_mode_returns_failure_on_exception(): void
    {
        $this->mockApp();

        Mockery::mock('overload:' . Connection::class);

        $runner = Mockery::mock('overload:' . MigrationRunner::class);
        $runner->allows('run')->andThrow(new \Exception('tenant db error'));

        $tester = new CommandTester(new MigrateAllTenantsCommand());
        $code   = $tester->execute(['--tenant' => 'acme']);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('tenant db error', $tester->getDisplay());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_returns_success_for_shared_strategy(): void
    {
        $this->mockApp('shared');

        $tester = new CommandTester(new MigrateAllTenantsCommand());
        $code   = $tester->execute([]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('database strategy', $tester->getDisplay());
    }
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_orchestrator_returns_success_when_no_tenants_found(): void
    {
        $this->mockApp();

        $tenantRepo = Mockery::mock('overload:' . TenantRepository::class);
        $tenantRepo->allows('allActive')->andReturn([]);

        $tester = new CommandTester(new MigrateAllTenantsCommand());
        $code   = $tester->execute([]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('No tenants found', $tester->getDisplay());
    }

    public function test_command_name(): void
    {
        $this->assertSame('migrate:all-tenants', (new MigrateAllTenantsCommand())->getName());
    }

    public function test_command_description(): void
    {
        $this->assertStringContainsString('tenant', (new MigrateAllTenantsCommand())->getDescription());
    }

    public function test_command_has_parallel_option(): void
    {
        $def = (new MigrateAllTenantsCommand())->getDefinition();
        $this->assertTrue($def->hasOption('parallel'));
        $this->assertSame(5, $def->getOption('parallel')->getDefault());
    }

    public function test_command_has_tenant_option(): void
    {
        $def = (new MigrateAllTenantsCommand())->getDefinition();
        $this->assertTrue($def->hasOption('tenant'));
    }

    public function test_command_has_dry_run_option(): void
    {
        $def = (new MigrateAllTenantsCommand())->getDefinition();
        $this->assertTrue($def->hasOption('dry-run'));
    }
}

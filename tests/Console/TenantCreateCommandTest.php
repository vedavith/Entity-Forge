<?php

namespace Tests\Console;

use EntityForge\Console\TenantCreateCommand;
use EntityForge\Core\Application;
use EntityForge\Tenant\TenantService;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class TenantCreateCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    private function mockApp(): void
    {
        $config = [
            'tenancy'  => ['strategy' => 'database', 'enabled' => false],
            'database' => [
                'driver' => 'mysql', 'host' => 'localhost', 'port' => 3306,
                'database' => 'app', 'username' => 'root', 'password' => 'root',
            ],
        ];

        $app = Mockery::mock('overload:' . Application::class);
        $app->allows('boot');
        $app->allows('getConfig')->andReturn($config);
        $app->allows('getContainer')->andReturn(null);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_execute_onboards_tenant_successfully(): void
    {
        $this->mockApp();

        $service = Mockery::mock('overload:' . TenantService::class);
        $service->allows('onboard')->with('acme', 'acme')->once();

        $tester = new CommandTester(new TenantCreateCommand());
        $code   = $tester->execute(['tenantId' => 'acme']);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('acme', $tester->getDisplay());
        $this->assertStringContainsString('successfully', $tester->getDisplay());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_execute_uses_name_option_when_provided(): void
    {
        $this->mockApp();

        $service = Mockery::mock('overload:' . TenantService::class);
        $service->allows('onboard')->with('acme', 'Acme Corp')->once();

        $tester = new CommandTester(new TenantCreateCommand());
        $code   = $tester->execute(['tenantId' => 'acme', '--name' => 'Acme Corp']);

        $this->assertSame(0, $code);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_execute_returns_failure_on_exception(): void
    {
        $this->mockApp();

        $service = Mockery::mock('overload:' . TenantService::class);
        $service->allows('onboard')->andThrow(new \Exception('already exists'));

        $tester = new CommandTester(new TenantCreateCommand());
        $code   = $tester->execute(['tenantId' => 'acme']);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('already exists', $tester->getDisplay());
    }
}
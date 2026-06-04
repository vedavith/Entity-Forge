<?php

namespace Tests\Tenant;

use EntityForge\Tenant\TenantProvisioner;
use EntityForge\Tenant\TenantRepository;
use EntityForge\Tenant\TenantService;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

class TenantServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    private function config(): array
    {
        return [
            'database' => [
                'driver' => 'mysql', 'host' => 'localhost', 'port' => 3306,
                'database' => 'app', 'username' => 'root', 'password' => 'root',
            ],
        ];
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_onboard_provisions_and_registers_tenant(): void
    {
        $repo = Mockery::mock('overload:' . TenantRepository::class);
        $repo->allows('exists')->with('acme')->andReturn(false);
        $repo->allows('create')->with('acme', 'Acme Corp')->once();

        $provisioner = Mockery::mock('overload:' . TenantProvisioner::class);
        $provisioner->allows('create')->with('acme')->once();

        $service = new TenantService($this->config());
        $service->onboard('acme', 'Acme Corp');

        $this->assertTrue(true);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_onboard_throws_when_tenant_already_exists(): void
    {
        $repo = Mockery::mock('overload:' . TenantRepository::class);
        $repo->allows('exists')->with('acme')->andReturn(true);

        Mockery::mock('overload:' . TenantProvisioner::class);

        $service = new TenantService($this->config());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Tenant already exists/');

        $service->onboard('acme', 'Acme Corp');
    }
}

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

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_suspend_sets_status_to_suspended(): void
    {
        $repo = Mockery::mock('overload:' . TenantRepository::class);
        $repo->allows('exists')->with('acme')->andReturn(true);
        $repo->allows('updateStatus')->with('acme', 'suspended')->once();

        Mockery::mock('overload:' . TenantProvisioner::class);

        $service = new TenantService($this->config());
        $service->suspend('acme');

        $this->assertTrue(true);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_suspend_throws_when_tenant_not_found(): void
    {
        $repo = Mockery::mock('overload:' . TenantRepository::class);
        $repo->allows('exists')->with('ghost')->andReturn(false);

        Mockery::mock('overload:' . TenantProvisioner::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Tenant not found/');

        $service = new TenantService($this->config());
        $service->suspend('ghost');
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_resume_sets_status_to_active(): void
    {
        $repo = Mockery::mock('overload:' . TenantRepository::class);
        $repo->allows('exists')->with('acme')->andReturn(true);
        $repo->allows('updateStatus')->with('acme', 'active')->once();

        Mockery::mock('overload:' . TenantProvisioner::class);

        $service = new TenantService($this->config());
        $service->resume('acme');

        $this->assertTrue(true);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_offboard_deletes_tenant_in_shared_strategy(): void
    {
        $config = array_merge($this->config(), ['tenancy' => ['strategy' => 'shared']]);

        $repo = Mockery::mock('overload:' . TenantRepository::class);
        $repo->allows('exists')->with('acme')->andReturn(true);
        $repo->allows('deleteByTenantId')->with('acme')->once();

        Mockery::mock('overload:' . TenantProvisioner::class);

        $service = new TenantService($config);
        $service->offboard('acme');

        $this->assertTrue(true);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_offboard_drops_db_and_deletes_tenant_in_database_strategy(): void
    {
        $config = array_merge($this->config(), ['tenancy' => ['strategy' => 'database']]);

        $repo = Mockery::mock('overload:' . TenantRepository::class);
        $repo->allows('exists')->with('acme')->andReturn(true);
        $repo->allows('deleteByTenantId')->with('acme')->once();

        $provisioner = Mockery::mock('overload:' . TenantProvisioner::class);
        $provisioner->allows('drop')->with('acme')->once();

        $service = new TenantService($config);
        $service->offboard('acme');

        $this->assertTrue(true);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_offboard_throws_when_tenant_not_found(): void
    {
        $repo = Mockery::mock('overload:' . TenantRepository::class);
        $repo->allows('exists')->with('ghost')->andReturn(false);

        Mockery::mock('overload:' . TenantProvisioner::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Tenant not found/');

        $service = new TenantService($this->config());
        $service->offboard('ghost');
    }
}

<?php

namespace Tests\Tenant;

use EntityForge\Tenant\TenantProvisioner;
use EntityForge\Tenant\TenantRepository;
use EntityForge\Tenant\TenantService;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class TenantServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    private function config(string $strategy = 'shared'): array
    {
        return [
            'tenancy'  => ['strategy' => $strategy],
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

    private function service(
        string $strategy = 'shared',
        ?TenantRepository $repo = null,
        ?TenantProvisioner $provisioner = null
    ): TenantService {
        return new TenantService(
            $this->config($strategy),
            $repo        ?? Mockery::mock(TenantRepository::class),
            $provisioner ?? Mockery::mock(TenantProvisioner::class)
        );
    }

    public function test_onboard_throws_for_invalid_tenant_id(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Invalid tenant ID/');

        $this->service()->onboard('acme corp!', 'Acme Corp');
    }

    public function test_onboard_accepts_valid_tenant_id_formats(): void
    {
        /** @var MockInterface&TenantRepository $repo */
        $repo = Mockery::mock(TenantRepository::class);
        $repo->allows('exists')->andReturn(false);
        $repo->allows('create');

        /** @var MockInterface&TenantProvisioner $provisioner */
        $provisioner = Mockery::mock(TenantProvisioner::class);
        $provisioner->allows('create');

        $service = new TenantService($this->config(), $repo, $provisioner);

        foreach (['acme', 'acme-corp', 'acme_corp', 'Acme123'] as $id) {
            $service->onboard($id, 'Test');
        }

        $this->assertTrue(true);
    }

    public function test_onboard_database_strategy_provisions_and_registers_tenant(): void
    {
        /** @var MockInterface&TenantRepository $repo */
        $repo = Mockery::mock(TenantRepository::class);
        $repo->allows('exists')->with('acme')->andReturn(false);
        $repo->expects('create')->with('acme', 'Acme Corp')->once();

        /** @var MockInterface&TenantProvisioner $provisioner */
        $provisioner = Mockery::mock(TenantProvisioner::class);
        $provisioner->expects('create')->with('acme')->once();

        $service = new TenantService($this->config('database'), $repo, $provisioner);
        $service->onboard('acme', 'Acme Corp');

        $this->assertTrue(true);
    }

    public function test_onboard_shared_strategy_registers_without_provisioning(): void
    {
        /** @var MockInterface&TenantRepository $repo */
        $repo = Mockery::mock(TenantRepository::class);
        $repo->allows('exists')->with('acme')->andReturn(false);
        $repo->expects('create')->with('acme', 'Acme Corp')->once();

        /** @var MockInterface&TenantProvisioner $provisioner */
        $provisioner = Mockery::mock(TenantProvisioner::class);
        $provisioner->expects('create')->never();

        $service = new TenantService($this->config('shared'), $repo, $provisioner);
        $service->onboard('acme', 'Acme Corp');

        $this->assertTrue(true);
    }

    public function test_onboard_throws_when_tenant_already_exists(): void
    {
        /** @var MockInterface&TenantRepository $repo */
        $repo = Mockery::mock(TenantRepository::class);
        $repo->allows('exists')->with('acme')->andReturn(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Tenant already exists/');

        $this->service(repo: $repo)->onboard('acme', 'Acme Corp');
    }

    public function test_suspend_sets_status_to_suspended(): void
    {
        /** @var MockInterface&TenantRepository $repo */
        $repo = Mockery::mock(TenantRepository::class);
        $repo->allows('exists')->with('acme')->andReturn(true);
        $repo->expects('updateStatus')->with('acme', 'suspended')->once();

        $this->service(repo: $repo)->suspend('acme');

        $this->assertTrue(true);
    }

    public function test_suspend_throws_when_tenant_not_found(): void
    {
        /** @var MockInterface&TenantRepository $repo */
        $repo = Mockery::mock(TenantRepository::class);
        $repo->allows('exists')->with('ghost')->andReturn(false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Tenant not found/');

        $this->service(repo: $repo)->suspend('ghost');
    }

    public function test_resume_sets_status_to_active(): void
    {
        /** @var MockInterface&TenantRepository $repo */
        $repo = Mockery::mock(TenantRepository::class);
        $repo->allows('exists')->with('acme')->andReturn(true);
        $repo->expects('updateStatus')->with('acme', 'active')->once();

        $this->service(repo: $repo)->resume('acme');

        $this->assertTrue(true);
    }

    public function test_offboard_deletes_tenant_in_shared_strategy(): void
    {
        /** @var MockInterface&TenantRepository $repo */
        $repo = Mockery::mock(TenantRepository::class);
        $repo->allows('exists')->with('acme')->andReturn(true);
        $repo->expects('deleteByTenantId')->with('acme')->once();

        $this->service('shared', $repo)->offboard('acme');

        $this->assertTrue(true);
    }

    public function test_offboard_drops_db_and_deletes_tenant_in_database_strategy(): void
    {
        /** @var MockInterface&TenantRepository $repo */
        $repo = Mockery::mock(TenantRepository::class);
        $repo->allows('exists')->with('acme')->andReturn(true);
        $repo->expects('deleteByTenantId')->with('acme')->once();

        /** @var MockInterface&TenantProvisioner $provisioner */
        $provisioner = Mockery::mock(TenantProvisioner::class);
        $provisioner->expects('drop')->with('acme')->once();

        $this->service('database', $repo, $provisioner)->offboard('acme');

        $this->assertTrue(true);
    }

    public function test_offboard_throws_when_tenant_not_found(): void
    {
        /** @var MockInterface&TenantRepository $repo */
        $repo = Mockery::mock(TenantRepository::class);
        $repo->allows('exists')->with('ghost')->andReturn(false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Tenant not found/');

        $this->service(repo: $repo)->offboard('ghost');
    }
}
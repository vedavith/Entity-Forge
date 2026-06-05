<?php

namespace EntityForge\Tenant;

class TenantService
{
    private TenantRepository $repo;
    private TenantProvisioner $provisioner;
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->repo = new TenantRepository($config);
        $this->provisioner = new TenantProvisioner($config);
    }

    public function onboard(string $tenantId, string $name): void
    {
        if ($this->repo->exists($tenantId)) {
            throw new \Exception("Tenant already exists: {$tenantId}");
        }

        $this->provisioner->create($tenantId);
        $this->repo->create($tenantId, $name);
    }

    public function suspend(string $tenantId): void
    {
        $this->assertExists($tenantId);
        $this->repo->updateStatus($tenantId, 'suspended');
    }

    public function resume(string $tenantId): void
    {
        $this->assertExists($tenantId);
        $this->repo->updateStatus($tenantId, 'active');
    }

    public function offboard(string $tenantId): void
    {
        $this->assertExists($tenantId);

        $strategy = $this->config['tenancy']['strategy'] ?? 'shared';

        if ($strategy === 'database') {
            $this->provisioner->drop($tenantId);
        }

        $this->repo->deleteByTenantId($tenantId);
    }

    private function assertExists(string $tenantId): void
    {
        if (!$this->repo->exists($tenantId)) {
            throw new \Exception("Tenant not found: {$tenantId}");
        }
    }
}
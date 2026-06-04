<?php

namespace EntityForge\Tenant;

class TenantService
{
    private TenantRepository $repo;
    private TenantProvisioner $provisioner;

    public function __construct(array $config)
    {
        $this->repo = new TenantRepository($config);
        $this->provisioner = new TenantProvisioner($config);
    }

    public function onboard(string $tenantId, string $name): void
    {
        if ($this->repo->exists($tenantId)) {
            throw new \Exception("Tenant already exists: {$tenantId}");
        }

        // 1. Create DB + run migrations
        $this->provisioner->create($tenantId);

        // 2. Register tenant
        $this->repo->create($tenantId, $name);
    }
}
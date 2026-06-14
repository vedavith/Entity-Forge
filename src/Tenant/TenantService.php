<?php

namespace EntityForge\Tenant;

class TenantService
{
    private TenantRepository $repo;
    private TenantProvisioner $provisioner;
    /** @var array<string, mixed> */
    private array $config;

    /** @param array<string, mixed> $config */
    public function __construct(
        array $config,
        ?TenantRepository $repo = null,
        ?TenantProvisioner $provisioner = null
    ) {
        $this->config      = $config;
        $this->repo        = $repo        ?? new TenantRepository($config);
        $this->provisioner = $provisioner ?? new TenantProvisioner($config);
    }

    public function onboard(string $tenantId, string $name): void
    {
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $tenantId)) {
            throw new \Exception("Invalid tenant ID '{$tenantId}': only letters, numbers, hyphens, and underscores are allowed.");
        }

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
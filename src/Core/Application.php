<?php

namespace EntityForge\Core;

use EntityForge\Config\ConfigLoader;
use EntityForge\Config\ConfigValidator;
use EntityForge\Core\Container;
use EntityForge\Core\CoreSchemaManager;
use EntityForge\Tenant\TenantContext;
use EntityForge\Tenant\TenantRepository;
use EntityForge\Tenant\TenantResolverFactory;
use Exception;

class Application
{
    private array $config;
    private string $configPath;
    private Container $container;

    public function __construct(string $configPath)
    {
        $this->configPath = rtrim($configPath, '/');
        $this->container  = new Container();
    }

    /**
     * @throws Exception
     */
    public function boot(array $context = [], bool $resolveTenant = true): void
    {
        $loader = new ConfigLoader();
        $validator = new ConfigValidator();

        $this->config = $loader->loadMultiple([
            $this->configPath . '/saas.yaml',
            $this->configPath . '/application.yaml'
        ]);

        $validator->validate($this->config);

        $this->registerBindings();

        // Always run CoreSchemaManager — idempotently creates the tenants registry
        // in both strategies (shared needs it too for tenant lookups and status checks)
        (new CoreSchemaManager($this->config))->ensure();

        $strategy = $this->config['tenancy']['strategy'] ?? 'shared';

        // 🔒 Tenant resolution is explicit
        if ($resolveTenant && ($this->config['tenancy']['enabled'] ?? false)) {
            $this->resolveTenant($context);

            // For database strategy, verify tenant exists and is not suspended
            if ($strategy === 'database') {
                $this->assertTenantActive();
            }
        }
    }

    private function registerBindings(): void
    {
        $config = $this->config;

        $this->container->singleton(
            \EntityForge\Tenant\TenantRepository::class,
            fn() => new \EntityForge\Tenant\TenantRepository($config)
        );

        $this->container->singleton(
            \EntityForge\Tenant\TenantProvisioner::class,
            fn() => new \EntityForge\Tenant\TenantProvisioner($config)
        );

        $this->container->singleton(
            \EntityForge\Tenant\TenantService::class,
            fn(Container $c) => new \EntityForge\Tenant\TenantService(
                $config,
                $c->make(\EntityForge\Tenant\TenantRepository::class),
                $c->make(\EntityForge\Tenant\TenantProvisioner::class)
            )
        );
    }

    /**
     * @throws Exception
     */
    private function assertTenantActive(): void
    {
        $tenantId = TenantContext::getTenantId();
        $repo = new TenantRepository($this->config);
        $tenant = $repo->findByTenantId($tenantId);

        if (!$tenant) {
            throw new Exception("Tenant not found: {$tenantId}");
        }

        if (($tenant['status'] ?? 'active') !== 'active') {
            throw new Exception("Tenant is suspended: {$tenantId}");
        }
    }

    /**
     * @throws Exception
     */
    private function resolveTenant(array $context): void
    {
        if (empty($context)) {
            throw new Exception("Tenant resolution requires context.");
        }

        $resolver = TenantResolverFactory::create($this->config);

        $tenantId = $resolver->resolve($context);

        TenantContext::setTenantId($tenantId);
    }

    /**
     * @throws Exception
     */
    public function getConfig(): array
    {
        if (empty($this->config)) {
            throw new Exception("Application not booted or config not loaded.");
        }

        return $this->config;
    }

    public function getContainer(): Container
    {
        return $this->container;
    }
}
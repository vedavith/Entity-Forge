<?php

namespace EntityForge\Core;
use EntityForge\Config\ConfigLoader;
use EntityForge\Config\ConfigValidator;
use EntityForge\Core\CoreSchemaManager;
use EntityForge\Tenant\TenantContext;
use EntityForge\Tenant\TenantResolverFactory;
use Exception;

class Application
{
    private array $config;
    private string $configPath;

    public function __construct(string $configPath)
    {
        $this->configPath = rtrim($configPath, '/');
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

        // 🔒 Tenant resolution is explicit
        if ($resolveTenant && ($this->config['tenancy']['enabled'] ?? false)) {
            $this->resolveTenant($context);
        }
    }

    /**
     * @throws Exception
     */
    private function resolveTenant(array $context): void
    {
        if (empty($context)) {
            throw new \Exception("Tenant resolution requires context.");
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
}
<?php

namespace EntityForge\Core;
use EntityForge\Config\ConfigLoader;
use EntityForge\Config\ConfigValidator;
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
    public function boot(array $context): void
    {
        $loader = new ConfigLoader();
        $validator = new ConfigValidator();

        try {
            $this->config = $loader->loadMultiple([
                $this->configPath . '/saas.yaml',
                $this->configPath . '/application.yaml'
            ]);

            $validator->validate($this->config);

            if ($this->config['tenancy']['enabled'] ?? false) {
                $resolver = TenantResolverFactory::create($this->config);
                $tenantId = $resolver->resolve($context);

                TenantContext::setTenantId($tenantId);
            }
        } catch (\Throwable $e) {
            throw new \Exception("Application boot failed: " . $e->getMessage());
        }
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
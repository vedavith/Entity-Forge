<?php

namespace Tests\Core;

use EntityForge\Core\Application;
use EntityForge\Tenant\TenantContext;
use Exception;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        TenantContext::clear();
        $this->tmpDir = sys_get_temp_dir() . '/ef_app_' . uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        array_map('unlink', glob($this->tmpDir . '/*.yaml'));
        rmdir($this->tmpDir);
    }

    private function writeConfig(array $tenancy = [], array $db = []): void
    {
        $saas = "tenancy:\n  enabled: true\n  strategy: shared\n  resolver: header\n  header_key: X-Tenant-ID\n";
        file_put_contents($this->tmpDir . '/saas.yaml', $saas);

        $dbConfig = array_merge(['driver' => 'mysql', 'host' => 'localhost', 'port' => 3306, 'database' => 'app', 'username' => 'root', 'password' => 'root'], $db);
        $app = "application:\n  name: test\n";
        foreach ($tenancy as $k => $v) {
            $app .= "tenancy:\n  {$k}: " . ($v === true ? 'true' : ($v === false ? 'false' : $v)) . "\n";
        }
        $app .= "database:\n";
        foreach ($dbConfig as $k => $v) {
            $app .= "  {$k}: {$v}\n";
        }
        file_put_contents($this->tmpDir . '/application.yaml', $app);
    }

    public function test_boot_loads_config(): void
    {
        $this->writeConfig();

        $app = new Application($this->tmpDir);
        $app->boot([], false);

        $config = $app->getConfig();
        $this->assertArrayHasKey('tenancy', $config);
        $this->assertArrayHasKey('database', $config);
    }

    public function test_boot_resolves_tenant_from_header(): void
    {
        $this->writeConfig();

        $app = new Application($this->tmpDir);
        $app->boot(['headers' => ['X-Tenant-ID' => 'acme']], true);

        $this->assertSame('acme', TenantContext::getTenantId());
    }

    public function test_boot_skips_tenant_resolution_when_disabled(): void
    {
        $this->writeConfig();

        $app = new Application($this->tmpDir);
        $app->boot([], false);

        $this->assertFalse(TenantContext::hasTenantId());
    }

    public function test_boot_throws_when_context_empty_and_tenant_enabled(): void
    {
        $this->writeConfig();

        $app = new Application($this->tmpDir);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Tenant resolution requires context');

        $app->boot([], true);
    }

    public function test_get_config_throws_before_boot(): void
    {
        $app = new Application($this->tmpDir);

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/not booted/');

        $app->getConfig();
    }

    public function test_get_config_returns_merged_config_after_boot(): void
    {
        $this->writeConfig();

        $app = new Application($this->tmpDir);
        $app->boot([], false);

        $config = $app->getConfig();
        $this->assertSame('shared', $config['tenancy']['strategy']);
        $this->assertSame('localhost', $config['database']['host']);
    }
}

<?php

namespace Tests\Config;

use EntityForge\Config\ConfigLoader;
use Exception;
use PHPUnit\Framework\TestCase;

class ConfigLoaderTest extends TestCase
{
    private ConfigLoader $loader;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->loader = new ConfigLoader();
        $this->tmpDir = sys_get_temp_dir() . '/ef_test_' . uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->tmpDir . '/*'));
        rmdir($this->tmpDir);
    }

    public function test_load_yaml_file(): void
    {
        $path = $this->tmpDir . '/config.yaml';
        file_put_contents($path, "tenancy:\n  enabled: true\n");

        $result = $this->loader->load($path);

        $this->assertSame(['tenancy' => ['enabled' => true]], $result);
    }

    public function test_load_yml_extension(): void
    {
        $path = $this->tmpDir . '/config.yml';
        file_put_contents($path, "database:\n  host: localhost\n");

        $result = $this->loader->load($path);

        $this->assertSame(['database' => ['host' => 'localhost']], $result);
    }

    public function test_load_json_file(): void
    {
        $path = $this->tmpDir . '/config.json';
        file_put_contents($path, json_encode(['entity' => 'User']));

        $result = $this->loader->load($path);

        $this->assertSame(['entity' => 'User'], $result);
    }

    public function test_load_throws_on_missing_file(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Config file not found/');

        $this->loader->load('/nonexistent/path/config.yaml');
    }

    public function test_load_throws_on_unsupported_extension(): void
    {
        $path = $this->tmpDir . '/config.ini';
        file_put_contents($path, '[section]');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Unsupported config format/');

        $this->loader->load($path);
    }

    public function test_load_multiple_merges_with_later_file_winning(): void
    {
        $first = $this->tmpDir . '/saas.yaml';
        $second = $this->tmpDir . '/app.yaml';
        file_put_contents($first, "tenancy:\n  strategy: shared\n  enabled: false\n");
        file_put_contents($second, "tenancy:\n  strategy: database\n");

        $result = $this->loader->loadMultiple([$first, $second]);

        $this->assertSame('database', $result['tenancy']['strategy']);
        $this->assertFalse($result['tenancy']['enabled']);
    }

    public function test_load_multiple_deep_merges_nested_keys(): void
    {
        $first = $this->tmpDir . '/a.yaml';
        $second = $this->tmpDir . '/b.yaml';
        file_put_contents($first, "database:\n  host: localhost\n  port: 3306\n");
        file_put_contents($second, "database:\n  host: db.prod\n");

        $result = $this->loader->loadMultiple([$first, $second]);

        $this->assertSame('db.prod', $result['database']['host']);
        $this->assertSame(3306, $result['database']['port']);
    }
}

<?php

namespace Tests\Config;

use EntityForge\Config\ConfigValidator;
use Exception;
use PHPUnit\Framework\TestCase;

class ConfigValidatorTest extends TestCase
{
    private ConfigValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ConfigValidator();
    }

    private function fullConfig(): array
    {
        return [
            'tenancy' => ['enabled' => true],
            'database' => [
                'driver' => 'mysql', 'host' => 'localhost', 'port' => 3306,
                'database' => 'app', 'username' => 'root', 'password' => 'root',
            ],
        ];
    }

    public function test_valid_config_passes(): void
    {
        $this->validator->validate($this->fullConfig());
        $this->assertTrue(true);
    }

    public function test_missing_tenancy_enabled_throws(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/Missing 'tenancy.enabled'/");

        $this->validator->validate([]);
    }

    public function test_missing_tenancy_key_throws(): void
    {
        $this->expectException(Exception::class);

        $this->validator->validate(['database' => ['host' => 'localhost']]);
    }

    public function test_missing_database_driver_throws(): void
    {
        $config = $this->fullConfig();
        unset($config['database']['driver']);

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/Missing 'database\.driver'/");

        $this->validator->validate($config);
    }

    public function test_missing_database_host_throws(): void
    {
        $config = $this->fullConfig();
        unset($config['database']['host']);

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/Missing 'database\.host'/");

        $this->validator->validate($config);
    }

    public function test_missing_database_password_throws(): void
    {
        $config = $this->fullConfig();
        unset($config['database']['password']);

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches("/Missing 'database\.password'/");

        $this->validator->validate($config);
    }
}

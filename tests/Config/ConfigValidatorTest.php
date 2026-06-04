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

    public function test_valid_config_passes(): void
    {
        $this->validator->validate(['tenancy' => ['enabled' => true]]);
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
}

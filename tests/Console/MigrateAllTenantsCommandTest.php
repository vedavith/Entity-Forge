<?php

namespace Tests\Console;

use EntityForge\Console\MigrateAllTenantsCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class MigrateAllTenantsCommandTest extends TestCase
{
    public function test_command_name(): void
    {
        $this->assertSame('migrate:all-tenants', (new MigrateAllTenantsCommand())->getName());
    }

    public function test_command_description(): void
    {
        $this->assertStringContainsString('tenant', (new MigrateAllTenantsCommand())->getDescription());
    }
}

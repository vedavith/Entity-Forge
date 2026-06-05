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

    public function test_command_has_parallel_option(): void
    {
        $def = (new MigrateAllTenantsCommand())->getDefinition();
        $this->assertTrue($def->hasOption('parallel'));
        $this->assertSame(5, $def->getOption('parallel')->getDefault());
    }

    public function test_command_has_tenant_option(): void
    {
        $def = (new MigrateAllTenantsCommand())->getDefinition();
        $this->assertTrue($def->hasOption('tenant'));
    }

    public function test_command_has_dry_run_option(): void
    {
        $def = (new MigrateAllTenantsCommand())->getDefinition();
        $this->assertTrue($def->hasOption('dry-run'));
    }
}

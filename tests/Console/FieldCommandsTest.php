<?php

namespace Tests\Console;

use EntityForge\Console\FieldAddCommand;
use EntityForge\Console\FieldListCommand;
use EntityForge\Console\FieldRemoveCommand;
use EntityForge\Core\Application;
use EntityForge\Tenant\TenantFieldRegistry;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class FieldCommandsTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    private function mockApp(): void
    {
        $config = [
            'tenancy'  => ['strategy' => 'shared'],
            'database' => [
                'driver' => 'mysql', 'host' => 'localhost', 'port' => 3306,
                'database' => 'app', 'username' => 'root', 'password' => 'root',
            ],
        ];

        $app = Mockery::mock('overload:' . Application::class);
        $app->allows('boot');
        $app->allows('getConfig')->andReturn($config);
    }

    // ── field:add ──────────────────────────────────────────────────────────────

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_field_add_registers_field_successfully(): void
    {
        $this->mockApp();

        $registry = Mockery::mock('overload:' . TenantFieldRegistry::class);
        $registry->allows('register')
            ->with('beta', 'accounts', 'vat_number', 'string', 'VAT Number', false)
            ->once();

        $tester = new CommandTester(new FieldAddCommand());
        $code   = $tester->execute([
            'entity'     => 'accounts',
            'field_name' => 'vat_number',
            'type'       => 'string',
            '--tenant'   => 'beta',
            '--label'    => 'VAT Number',
        ]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('vat_number', $tester->getDisplay());
        $this->assertStringContainsString('beta', $tester->getDisplay());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_field_add_defaults_label_to_field_name(): void
    {
        $this->mockApp();

        $registry = Mockery::mock('overload:' . TenantFieldRegistry::class);
        $registry->allows('register')
            ->with('beta', 'accounts', 'vat_number', 'string', 'vat_number', false)
            ->once();

        $tester = new CommandTester(new FieldAddCommand());
        $code   = $tester->execute([
            'entity'     => 'accounts',
            'field_name' => 'vat_number',
            'type'       => 'string',
            '--tenant'   => 'beta',
        ]);

        $this->assertSame(0, $code);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_field_add_returns_failure_without_tenant(): void
    {
        $tester = new CommandTester(new FieldAddCommand());
        $code   = $tester->execute([
            'entity'     => 'accounts',
            'field_name' => 'vat_number',
            'type'       => 'string',
        ]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('--tenant is required', $tester->getDisplay());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_field_add_returns_failure_on_invalid_field(): void
    {
        $this->mockApp();

        $registry = Mockery::mock('overload:' . TenantFieldRegistry::class);
        $registry->allows('register')->andThrow(new \InvalidArgumentException('Invalid field name'));

        $tester = new CommandTester(new FieldAddCommand());
        $code   = $tester->execute([
            'entity'     => 'accounts',
            'field_name' => 'Bad Name',
            'type'       => 'string',
            '--tenant'   => 'beta',
        ]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Invalid field name', $tester->getDisplay());
    }

    // ── field:list ─────────────────────────────────────────────────────────────

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_field_list_outputs_registered_fields(): void
    {
        $this->mockApp();

        $fields = [
            ['id' => 1, 'field_name' => 'vat_number', 'field_type' => 'string', 'label' => 'VAT Number', 'required' => 0],
        ];

        $registry = Mockery::mock('overload:' . TenantFieldRegistry::class);
        $registry->allows('fieldsFor')->with('beta', 'accounts')->andReturn($fields);

        $tester = new CommandTester(new FieldListCommand());
        $code   = $tester->execute(['entity' => 'accounts', '--tenant' => 'beta']);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('vat_number', $tester->getDisplay());
        $this->assertStringContainsString('VAT Number', $tester->getDisplay());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_field_list_shows_message_when_empty(): void
    {
        $this->mockApp();

        $registry = Mockery::mock('overload:' . TenantFieldRegistry::class);
        $registry->allows('fieldsFor')->andReturn([]);

        $tester = new CommandTester(new FieldListCommand());
        $code   = $tester->execute(['entity' => 'accounts', '--tenant' => 'beta']);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('No custom fields', $tester->getDisplay());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_field_list_returns_failure_without_tenant(): void
    {
        $tester = new CommandTester(new FieldListCommand());
        $code   = $tester->execute(['entity' => 'accounts']);

        $this->assertSame(1, $code);
    }

    // ── field:remove ───────────────────────────────────────────────────────────

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_field_remove_deletes_field(): void
    {
        $this->mockApp();

        $registry = Mockery::mock('overload:' . TenantFieldRegistry::class);
        $registry->allows('remove')->with(3, 'beta')->once();

        $tester = new CommandTester(new FieldRemoveCommand());
        $code   = $tester->execute(['id' => '3', '--tenant' => 'beta']);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('#3', $tester->getDisplay());
        $this->assertStringContainsString('beta', $tester->getDisplay());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_field_remove_returns_failure_without_tenant(): void
    {
        $tester = new CommandTester(new FieldRemoveCommand());
        $code   = $tester->execute(['id' => '3']);

        $this->assertSame(1, $code);
    }
}

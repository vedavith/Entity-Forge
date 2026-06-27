<?php

namespace Tests\Console;

use EntityForge\Console\GenerateAllCommand;
use EntityForge\Console\GenerateCommand;
use EntityForge\Console\MigrateCommand;
use EntityForge\Console\RollbackCommand;
use EntityForge\Console\TenantCreateCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ConsoleCommandsTest extends TestCase
{
    // ── Configuration ─────────────────────────────────────────────────────────

    public function test_generate_command_name(): void
    {
        $this->assertSame('generate', (new GenerateCommand())->getName());
    }

    public function test_generate_all_command_name(): void
    {
        $this->assertSame('generate:all', (new GenerateAllCommand())->getName());
    }

    public function test_migrate_command_name(): void
    {
        $this->assertSame('migrate', (new MigrateCommand())->getName());
    }

    public function test_rollback_command_name(): void
    {
        $this->assertSame('migrate:rollback', (new RollbackCommand())->getName());
    }

    public function test_tenant_create_command_name(): void
    {
        $this->assertSame('tenant:create', (new TenantCreateCommand())->getName());
    }

    // ── GenerateCommand::execute() ─────────────────────────────────────────────

    public function test_generate_fails_when_config_file_missing(): void
    {
        $tester = new CommandTester(new GenerateCommand());
        $code = $tester->execute(['entity' => 'Ghost', '--config' => '/nonexistent/dir']);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Config not found', $tester->getDisplay());
    }

    public function test_generate_fails_when_json_invalid(): void
    {
        $tmp = sys_get_temp_dir() . '/ef_con_' . uniqid();
        mkdir($tmp);
        file_put_contents($tmp . '/Bad.json', '{invalid json}');

        $tester = new CommandTester(new GenerateCommand());
        $code = $tester->execute(['entity' => 'Bad', '--config' => $tmp]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Invalid JSON', $tester->getDisplay());

        unlink($tmp . '/Bad.json');
        rmdir($tmp);
    }

    public function test_generate_succeeds_with_valid_schema(): void
    {
        $tmp = sys_get_temp_dir() . '/ef_con_' . uniqid();
        mkdir($tmp . '/entities', 0755, true);
        file_put_contents($tmp . '/entities/Widget.json', json_encode([
            'entity' => 'Widget',
            'fields' => ['id' => 'int', 'name' => 'string'],
        ]));

        $origDir = getcwd();
        chdir($tmp);

        $tester = new CommandTester(new GenerateCommand());
        $code = $tester->execute(['entity' => 'Widget', '--config' => 'entities']);

        chdir($origDir);
        $this->removeDir($tmp);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('Generated Widget', $tester->getDisplay());
    }

    public function test_generate_reads_strategy_from_application_yaml(): void
    {
        $tmp = sys_get_temp_dir() . '/ef_con_' . uniqid();
        mkdir($tmp . '/entities', 0755, true);
        mkdir($tmp . '/config', 0755, true);
        file_put_contents($tmp . '/entities/Widget.json', json_encode([
            'entity' => 'Widget',
            'fields' => ['name' => 'string'],
        ]));
        file_put_contents($tmp . '/config/application.yaml', "tenancy:\n  strategy: database\n");

        $origDir = getcwd();
        chdir($tmp);

        $tester = new CommandTester(new GenerateCommand());
        $code = $tester->execute(['entity' => 'Widget', '--config' => 'entities']);

        chdir($origDir);
        $this->removeDir($tmp);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('Generated Widget', $tester->getDisplay());
    }

    // ── GenerateAllCommand::execute() ──────────────────────────────────────────

    public function test_generate_all_fails_when_config_dir_missing(): void
    {
        $tmp = sys_get_temp_dir() . '/ef_con_' . uniqid();
        mkdir($tmp);
        $origDir = getcwd();
        chdir($tmp);

        $tester = new CommandTester(new GenerateAllCommand());
        $code = $tester->execute([]);

        chdir($origDir);
        rmdir($tmp);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Directory not found', $tester->getDisplay());
    }

    public function test_generate_all_succeeds_with_no_entity_files(): void
    {
        $tmp = sys_get_temp_dir() . '/ef_con_' . uniqid();
        mkdir($tmp . '/config/entities', 0755, true);
        $origDir = getcwd();
        chdir($tmp);

        $tester = new CommandTester(new GenerateAllCommand());
        $code = $tester->execute([]);

        chdir($origDir);
        $this->removeDir($tmp);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('No entity configs found', $tester->getDisplay());
    }

    public function test_generate_all_skips_cross_batch_dependency(): void
    {
        $tmp = sys_get_temp_dir() . '/ef_con_' . uniqid();
        mkdir($tmp . '/config/entities', 0755, true);
        // Post references ExternalUser which is NOT in the batch — the sort should skip it
        file_put_contents($tmp . '/config/entities/Post.json', json_encode([
            'entity' => 'Post',
            'fields' => ['id' => 'int', 'external_user_id' => 'int'],
            'relations' => ['belongsTo' => ['ExternalUser' => 'external_user_id']],
        ]));
        $origDir = getcwd();
        chdir($tmp);

        $tester = new CommandTester(new GenerateAllCommand());
        $code = $tester->execute([]);

        chdir($origDir);
        $this->removeDir($tmp);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('Generated Post', $tester->getDisplay());
    }

    public function test_generate_all_fails_on_circular_dependency(): void
    {
        $tmp = sys_get_temp_dir() . '/ef_con_' . uniqid();
        mkdir($tmp . '/config/entities', 0755, true);
        file_put_contents($tmp . '/config/entities/Alpha.json', json_encode([
            'entity' => 'Alpha',
            'fields' => ['id' => 'int'],
            'relations' => ['belongsTo' => ['Beta' => 'beta_id']],
        ]));
        file_put_contents($tmp . '/config/entities/Beta.json', json_encode([
            'entity' => 'Beta',
            'fields' => ['id' => 'int'],
            'relations' => ['belongsTo' => ['Alpha' => 'alpha_id']],
        ]));
        $origDir = getcwd();
        chdir($tmp);

        $tester = new CommandTester(new GenerateAllCommand());
        $code = $tester->execute([]);

        chdir($origDir);
        $this->removeDir($tmp);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Circular dependency', $tester->getDisplay());
    }

    public function test_generate_all_generates_all_entities(): void
    {
        $tmp = sys_get_temp_dir() . '/ef_con_' . uniqid();
        mkdir($tmp . '/config/entities', 0755, true);
        file_put_contents($tmp . '/config/entities/Item.json', json_encode([
            'entity' => 'Item',
            'fields' => ['id' => 'int'],
        ]));
        $origDir = getcwd();
        chdir($tmp);

        $tester = new CommandTester(new GenerateAllCommand());
        $code = $tester->execute([]);

        chdir($origDir);
        $this->removeDir($tmp);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('Generated Item', $tester->getDisplay());
    }

    public function test_generate_all_reads_strategy_from_application_yaml(): void
    {
        $tmp = sys_get_temp_dir() . '/ef_con_' . uniqid();
        mkdir($tmp . '/config/entities', 0755, true);
        file_put_contents($tmp . '/config/entities/Item.json', json_encode([
            'entity' => 'Item',
            'fields' => ['name' => 'string'],
        ]));
        file_put_contents($tmp . '/config/application.yaml', "tenancy:\n  strategy: database\n");

        $origDir = getcwd();
        chdir($tmp);

        $tester = new CommandTester(new GenerateAllCommand());
        $code = $tester->execute([]);

        chdir($origDir);
        $this->removeDir($tmp);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('Generated Item', $tester->getDisplay());
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (glob($dir . '/*') ?: [] as $item) {
            is_dir($item) ? $this->removeDir($item) : unlink($item);
        }
        rmdir($dir);
    }
}

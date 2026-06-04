<?php

namespace Tests\Generator;

use EntityForge\Generator\EntityGenerator;
use PHPUnit\Framework\TestCase;

class EntityGeneratorTest extends TestCase
{
    private EntityGenerator $generator;
    private string $tmpDir;
    private string $originalDir;

    protected function setUp(): void
    {
        $this->generator = new EntityGenerator();
        $this->tmpDir = sys_get_temp_dir() . '/ef_gen_' . uniqid();
        mkdir($this->tmpDir, 0755, true);
        $this->originalDir = getcwd();
        chdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        chdir($this->originalDir);
        $this->removeDir($this->tmpDir);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (glob($dir . '/*') as $item) {
            is_dir($item) ? $this->removeDir($item) : unlink($item);
        }
        rmdir($dir);
    }

    private function minimalConfig(string $entity = 'Widget'): array
    {
        return ['entity' => $entity, 'fields' => ['id' => 'int', 'name' => 'string']];
    }

    public function test_generate_creates_entity_file(): void
    {
        $this->generator->generate($this->minimalConfig());

        $this->assertFileExists($this->tmpDir . '/app/Entity/Widget.php');
    }

    public function test_generate_creates_repository_file(): void
    {
        $this->generator->generate($this->minimalConfig());

        $this->assertFileExists($this->tmpDir . '/app/Repository/WidgetRepository.php');
    }

    public function test_generate_with_migration_creates_up_file(): void
    {
        $this->generator->generate($this->minimalConfig(), true);

        $migrations = glob($this->tmpDir . '/database/migrations/*.up.sql');
        $this->assertCount(1, $migrations);
    }

    public function test_generate_with_migration_creates_down_file(): void
    {
        $this->generator->generate($this->minimalConfig(), true);

        $migrations = glob($this->tmpDir . '/database/migrations/*.down.sql');
        $this->assertCount(1, $migrations);
    }

    public function test_generate_without_migration_creates_no_migration_files(): void
    {
        $this->generator->generate($this->minimalConfig(), false);

        $migrations = glob($this->tmpDir . '/database/migrations/*.sql') ?: [];
        $this->assertCount(0, $migrations);
    }

    public function test_generate_multiple_entities_produce_unique_migration_names(): void
    {
        $this->generator->generate(['entity' => 'Alpha', 'fields' => ['id' => 'int']], true);
        $this->generator->generate(['entity' => 'Beta', 'fields' => ['id' => 'int']], true);

        $migrations = glob($this->tmpDir . '/database/migrations/*.up.sql');
        $this->assertCount(2, $migrations);
        $this->assertNotSame(basename($migrations[0]), basename($migrations[1]));
    }

    public function test_generate_throws_on_invalid_schema(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->generator->generate(['entity' => 'invalid_name', 'fields' => []]);
    }
}

<?php

namespace Tests\Generator\Builder;

use EntityForge\Generator\Builder\RepositoryBuilder;
use EntityForge\Generator\Schema\EntitySchema;
use PHPUnit\Framework\TestCase;

class RepositoryBuilderTest extends TestCase
{
    private RepositoryBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new RepositoryBuilder();
    }

    public function test_generates_correct_class_name(): void
    {
        $schema = new EntitySchema(['entity' => 'Invoice', 'fields' => []]);
        $code = $this->builder->build($schema);

        $this->assertStringContainsString('class InvoiceRepository', $code);
    }

    public function test_extends_base_repository(): void
    {
        $schema = new EntitySchema(['entity' => 'Invoice', 'fields' => []]);
        $code = $this->builder->build($schema);

        $this->assertStringContainsString('extends BaseRepository', $code);
    }

    public function test_uses_correct_namespace(): void
    {
        $schema = new EntitySchema(['entity' => 'Invoice', 'fields' => []]);
        $code = $this->builder->build($schema);

        $this->assertStringContainsString('namespace App\\Repository', $code);
        $this->assertStringContainsString('use EntityForge\\Repository\\BaseRepository', $code);
    }

    public function test_generates_valid_php_opening(): void
    {
        $schema = new EntitySchema(['entity' => 'Foo', 'fields' => []]);
        $code = $this->builder->build($schema);

        $this->assertStringStartsWith('<?php', $code);
    }
}

<?php

namespace Tests\Generator\Schema;

use EntityForge\Generator\Schema\EntitySchema;
use PHPUnit\Framework\TestCase;

class EntitySchemaTest extends TestCase
{
    private function schema(array $overrides = []): EntitySchema
    {
        return new EntitySchema(array_merge([
            'entity' => 'Order',
            'fields' => ['id' => 'int', 'total' => 'float'],
        ], $overrides));
    }

    public function test_get_entity_name(): void
    {
        $this->assertSame('Order', $this->schema()->getEntityName());
    }

    public function test_has_timestamps_defaults_false(): void
    {
        $this->assertFalse($this->schema()->hasTimestamps());
    }

    public function test_has_timestamps_true_when_set(): void
    {
        $this->assertTrue($this->schema(['timestamps' => true])->hasTimestamps());
    }

    public function test_get_fields_returns_defined_fields(): void
    {
        $fields = $this->schema()->getFields();

        $this->assertArrayHasKey('id', $fields);
        $this->assertArrayHasKey('total', $fields);
    }

    public function test_get_fields_appends_timestamps_when_enabled(): void
    {
        $fields = $this->schema(['timestamps' => true])->getFields();

        $this->assertArrayHasKey('created_at', $fields);
        $this->assertArrayHasKey('updated_at', $fields);
    }

    public function test_get_fields_with_no_fields_key_returns_empty(): void
    {
        $schema = new EntitySchema(['entity' => 'Empty']);
        $this->assertSame([], $schema->getFields());
    }

    public function test_get_relations_returns_empty_array_by_default(): void
    {
        $this->assertSame([], $this->schema()->getRelations());
    }

    public function test_get_relations_returns_defined_relations(): void
    {
        $schema = $this->schema(['relations' => ['belongsTo' => ['User' => 'user_id']]]);
        $this->assertSame(['belongsTo' => ['User' => 'user_id']], $schema->getRelations());
    }

    public function test_get_indexes_returns_empty_array_by_default(): void
    {
        $this->assertSame([], $this->schema()->getIndexes());
    }

    public function test_get_indexes_returns_defined_indexes(): void
    {
        $indexes = [['columns' => ['email'], 'unique' => true]];
        $schema  = $this->schema(['indexes' => $indexes]);
        $this->assertSame($indexes, $schema->getIndexes());
    }

    public function test_get_primary_key_returns_id(): void
    {
        $this->assertSame('id', $this->schema()->getPrimaryKey());
    }
}

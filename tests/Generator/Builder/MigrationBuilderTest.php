<?php

namespace Tests\Generator\Builder;

use EntityForge\Generator\Builder\MigrationBuilder;
use EntityForge\Generator\Schema\EntitySchema;
use PHPUnit\Framework\TestCase;

class MigrationBuilderTest extends TestCase
{
    private MigrationBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new MigrationBuilder();
    }

    private function schema(array $fields, string $entity = 'Product', array $extra = []): EntitySchema
    {
        return new EntitySchema(array_merge(['entity' => $entity, 'fields' => $fields], $extra));
    }

    public function test_build_up_creates_correct_table_name(): void
    {
        $sql = $this->builder->buildUp($this->schema([], 'Product'));

        $this->assertStringContainsString('CREATE TABLE products', $sql);
    }

    public function test_build_up_always_includes_id_primary_key(): void
    {
        $sql = $this->builder->buildUp($this->schema([]));

        $this->assertStringContainsString('id INT AUTO_INCREMENT PRIMARY KEY', $sql);
    }

    public function test_build_up_always_includes_tenant_id(): void
    {
        $sql = $this->builder->buildUp($this->schema([]));

        $this->assertStringContainsString('tenant_id VARCHAR(255) NOT NULL', $sql);
    }

    public function test_build_up_database_strategy_omits_tenant_id(): void
    {
        $sql = $this->builder->buildUp($this->schema([]), [], 'database');

        $this->assertStringNotContainsString('tenant_id', $sql);
    }

    public function test_build_up_does_not_duplicate_id_when_in_schema_fields(): void
    {
        $sql = $this->builder->buildUp($this->schema(['id' => 'int']));

        $this->assertEquals(1, substr_count($sql, 'id INT'));
    }

    public function test_build_up_maps_int_to_int_column(): void
    {
        $sql = $this->builder->buildUp($this->schema(['count' => 'int']));

        $this->assertStringContainsString('count INT', $sql);
    }

    public function test_build_up_maps_string_to_varchar(): void
    {
        $sql = $this->builder->buildUp($this->schema(['name' => 'string']));

        $this->assertStringContainsString('name VARCHAR(255)', $sql);
    }

    public function test_build_up_maps_float_to_float(): void
    {
        $sql = $this->builder->buildUp($this->schema(['price' => 'float']));

        $this->assertStringContainsString('price FLOAT', $sql);
    }

    public function test_build_up_maps_bool_to_boolean(): void
    {
        $sql = $this->builder->buildUp($this->schema(['active' => 'bool']));

        $this->assertStringContainsString('active BOOLEAN', $sql);
    }

    public function test_build_up_maps_datetime_to_datetime(): void
    {
        $sql = $this->builder->buildUp($this->schema(['date' => 'datetime']));

        $this->assertStringContainsString('date DATETIME', $sql);
    }

    public function test_build_down_drops_correct_table(): void
    {
        $sql = $this->builder->buildDown($this->schema([], 'Order'));

        $this->assertStringContainsString('DROP TABLE IF EXISTS orders', $sql);
    }

    public function test_table_name_is_lowercased_plural(): void
    {
        $sql = $this->builder->buildUp($this->schema([], 'Invoice'));

        $this->assertStringContainsString('invoices', $sql);
    }

    public function test_build_up_emits_fk_column_and_constraint(): void
    {
        $schema = new EntitySchema([
            'entity'    => 'Order',
            'fields'    => [],
            'relations' => ['belongsTo' => ['User' => 'user_id']],
        ]);

        $sql = $this->builder->buildUp($schema);

        $this->assertStringContainsString('user_id INT NOT NULL', $sql);
        $this->assertStringContainsString('CONSTRAINT fk_orders_user_id', $sql);
        $this->assertStringContainsString('FOREIGN KEY (user_id) REFERENCES users(id)', $sql);
    }

    public function test_build_up_does_not_duplicate_fk_column_when_in_schema_fields(): void
    {
        $schema = new EntitySchema([
            'entity'    => 'Order',
            'fields'    => ['user_id' => 'int'],
            'relations' => ['belongsTo' => ['User' => 'user_id']],
        ]);

        $sql = $this->builder->buildUp($schema);

        $this->assertEquals(1, substr_count($sql, 'user_id INT'));
    }

    public function test_build_up_emits_index(): void
    {
        $schema = new EntitySchema([
            'entity'  => 'Order',
            'fields'  => ['status' => 'string'],
            'indexes' => [['columns' => ['status']]],
        ]);

        $sql = $this->builder->buildUp($schema);

        $this->assertStringContainsString('INDEX idx_orders_status (status)', $sql);
    }

    public function test_build_up_emits_unique_index_shared_prepends_tenant_id(): void
    {
        $schema = new EntitySchema([
            'entity'  => 'User',
            'fields'  => ['email' => 'string'],
            'indexes' => [['columns' => ['email'], 'unique' => true]],
        ]);

        $sql = $this->builder->buildUp($schema, [], 'shared');

        $this->assertStringContainsString('UNIQUE INDEX uix_users_tenant_id_email (tenant_id, email)', $sql);
    }

    public function test_build_up_emits_unique_index_database_strategy_no_tenant_id(): void
    {
        $schema = new EntitySchema([
            'entity'  => 'User',
            'fields'  => ['email' => 'string'],
            'indexes' => [['columns' => ['email'], 'unique' => true]],
        ]);

        $sql = $this->builder->buildUp($schema, [], 'database');

        $this->assertStringContainsString('UNIQUE INDEX uix_users_email (email)', $sql);
        $this->assertStringNotContainsString('tenant_id', $sql);
    }

    public function test_build_up_emits_composite_index(): void
    {
        $schema = new EntitySchema([
            'entity'  => 'Order',
            'fields'  => ['status' => 'string'],
            'indexes' => [['columns' => ['user_id', 'status']]],
        ]);

        $sql = $this->builder->buildUp($schema);

        $this->assertStringContainsString('INDEX idx_orders_user_id_status (user_id, status)', $sql);
    }

    public function test_build_up_maps_unknown_type_to_text(): void
    {
        $sql = $this->builder->buildUp($this->schema(['data' => 'json']));

        $this->assertStringContainsString('data TEXT', $sql);
    }

    public function test_build_up_emits_multiple_fks_and_indexes(): void
    {
        $schema = new EntitySchema([
            'entity'    => 'OrderItem',
            'fields'    => [],
            'relations' => ['belongsTo' => ['Order' => 'order_id', 'Product' => 'product_id']],
            'indexes'   => [['columns' => ['order_id', 'product_id'], 'unique' => true]],
        ]);

        $sql = $this->builder->buildUp($schema);

        $this->assertStringContainsString('order_id INT NOT NULL', $sql);
        $this->assertStringContainsString('product_id INT NOT NULL', $sql);
        $this->assertStringContainsString('FOREIGN KEY (order_id) REFERENCES orders(id)', $sql);
        $this->assertStringContainsString('FOREIGN KEY (product_id) REFERENCES products(id)', $sql);
        $this->assertStringContainsString('UNIQUE INDEX uix_order_items_tenant_id_order_id_product_id', $sql);
    }
}

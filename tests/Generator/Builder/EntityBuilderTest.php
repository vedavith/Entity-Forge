<?php

namespace Tests\Generator\Builder;

use EntityForge\Generator\Builder\EntityBuilder;
use EntityForge\Generator\Schema\EntitySchema;
use PHPUnit\Framework\TestCase;

class EntityBuilderTest extends TestCase
{
    private EntityBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new EntityBuilder();
    }

    private function schema(array $config): EntitySchema
    {
        return new EntitySchema($config);
    }

    public function test_generates_class_with_correct_name(): void
    {
        $code = $this->builder->build($this->schema(['entity' => 'Invoice', 'fields' => []]));

        $this->assertStringContainsString('class Invoice', $code);
    }

    public function test_generates_correct_namespace(): void
    {
        $code = $this->builder->build($this->schema(['entity' => 'Invoice', 'fields' => []]));

        $this->assertStringContainsString('namespace App\\Entity', $code);
    }

    public function test_generates_int_property(): void
    {
        $code = $this->builder->build($this->schema([
            'entity' => 'Product',
            'fields' => ['quantity' => 'int'],
        ]));

        $this->assertStringContainsString('public int $quantity', $code);
    }

    public function test_generates_string_property(): void
    {
        $code = $this->builder->build($this->schema([
            'entity' => 'Product',
            'fields' => ['name' => 'string'],
        ]));

        $this->assertStringContainsString('public string $name', $code);
    }

    public function test_float_type_maps_to_float(): void
    {
        $code = $this->builder->build($this->schema([
            'entity' => 'Product',
            'fields' => ['price' => 'float'],
        ]));

        $this->assertStringContainsString('public float $price', $code);
    }

    public function test_bool_type_maps_to_bool(): void
    {
        $code = $this->builder->build($this->schema([
            'entity' => 'Product',
            'fields' => ['active' => 'bool'],
        ]));

        $this->assertStringContainsString('public bool $active', $code);
    }

    public function test_text_type_maps_to_string(): void
    {
        $code = $this->builder->build($this->schema([
            'entity' => 'Post',
            'fields' => ['body' => 'text'],
        ]));

        $this->assertStringContainsString('public string $body', $code);
    }

    public function test_datetime_type_maps_to_string(): void
    {
        $code = $this->builder->build($this->schema([
            'entity' => 'Event',
            'fields' => ['starts_at' => 'datetime'],
        ]));

        $this->assertStringContainsString('public string $starts_at', $code);
    }

    public function test_unknown_type_maps_to_mixed(): void
    {
        $code = $this->builder->build($this->schema([
            'entity' => 'Blob',
            'fields' => ['data' => 'json'],
        ]));

        $this->assertStringContainsString('public mixed $data', $code);
    }

    public function test_generates_belongs_to_relation_property(): void
    {
        $code = $this->builder->build($this->schema([
            'entity' => 'Order',
            'fields' => [],
            'relations' => ['belongsTo' => ['User' => 'user_id']],
        ]));

        $this->assertStringContainsString('public ?User $user = null', $code);
        $this->assertStringContainsString('Loaded via user_id', $code);
        $this->assertStringContainsString('use App\Entity\User', $code);
    }

    public function test_generates_has_many_relation_property(): void
    {
        $code = $this->builder->build($this->schema([
            'entity' => 'User',
            'fields' => [],
            'relations' => ['hasMany' => ['Order' => 'user_id']],
        ]));

        $this->assertStringContainsString('public array $orders = []', $code);
        $this->assertStringContainsString('@var Order[]', $code);
        $this->assertStringContainsString('Loaded via user_id', $code);
        $this->assertStringContainsString('use App\Entity\Order', $code);
    }

    public function test_pluralizes_consonant_y_to_ies(): void
    {
        $code = $this->builder->build($this->schema([
            'entity' => 'User',
            'fields' => [],
            'relations' => ['hasMany' => ['Category' => 'user_id']],
        ]));

        $this->assertStringContainsString('public array $categories = []', $code);
    }

    public function test_pluralizes_sibilant_ending_to_es(): void
    {
        $code = $this->builder->build($this->schema([
            'entity' => 'User',
            'fields' => [],
            'relations' => ['hasMany' => ['Bus' => 'user_id']],
        ]));

        $this->assertStringContainsString('public array $buses = []', $code);
    }

    public function test_generates_valid_php_opening(): void
    {
        $code = $this->builder->build($this->schema(['entity' => 'Foo', 'fields' => []]));

        $this->assertStringStartsWith('<?php', $code);
    }
}

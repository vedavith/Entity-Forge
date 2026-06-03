<?php

namespace Tests\Generator\Schema;

use EntityForge\Generator\Schema\SchemaValidator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SchemaValidatorTest extends TestCase
{
    private SchemaValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new SchemaValidator();
    }

    public function test_valid_schema_passes(): void
    {
        $this->validator->validate(['entity' => 'User', 'fields' => ['id' => 'int', 'name' => 'string']]);
        $this->assertTrue(true);
    }

    public function test_missing_entity_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches("/Missing Required field 'entity'/");

        $this->validator->validate([]);
    }

    public function test_empty_entity_name_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->validator->validate(['entity' => '']);
    }

    public function test_lowercase_entity_name_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Invalid entity name/');

        $this->validator->validate(['entity' => 'user']);
    }

    public function test_entity_name_with_underscore_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->validator->validate(['entity' => 'My_Entity']);
    }

    public function test_fields_as_non_array_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches("/'fields' must be an object/");

        $this->validator->validate(['entity' => 'User', 'fields' => 'invalid']);
    }

    public function test_invalid_field_name_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Invalid field name/');

        $this->validator->validate(['entity' => 'User', 'fields' => ['InvalidName' => 'string']]);
    }

    public function test_unsupported_field_type_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Unsupported field type/');

        $this->validator->validate(['entity' => 'User', 'fields' => ['name' => 'array']]);
    }

    public function test_all_supported_types_pass(): void
    {
        $this->validator->validate([
            'entity' => 'Product',
            'fields' => [
                'id'     => 'int',
                'name'   => 'string',
                'price'  => 'float',
                'active' => 'bool',
            ],
        ]);
        $this->assertTrue(true);
    }

    public function test_schema_without_fields_key_passes(): void
    {
        $this->validator->validate(['entity' => 'User']);
        $this->assertTrue(true);
    }
}

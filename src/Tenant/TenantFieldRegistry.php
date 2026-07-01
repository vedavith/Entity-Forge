<?php

namespace EntityForge\Tenant;

use EntityForge\Database\Connection;
use PDO;

class TenantFieldRegistry
{
    private PDO $pdo;

    /** @param array<string, mixed> $config */
    public function __construct(array $config)
    {
        $this->pdo = (new Connection($config['database']))->getPdo();
    }

    /**
     * Register a custom field for a tenant + entity combination.
     *
     * @throws \InvalidArgumentException
     */
    public function register(string $tenantId, string $entity, string $fieldName, string $fieldType, string $label, bool $required = false): void
    {
        $this->assertFieldName($fieldName);
        $this->assertFieldType($fieldType);

        $sql = <<<SQL
INSERT INTO tenant_fields (tenant_id, entity, field_name, field_type, label, required)
VALUES (:tenant_id, :entity, :field_name, :field_type, :label, :required)
SQL;

        $this->pdo->prepare($sql)->execute([
            'tenant_id'  => $tenantId,
            'entity'     => $entity,
            'field_name' => $fieldName,
            'field_type' => $fieldType,
            'label'      => $label,
            'required'   => $required ? 1 : 0,
        ]);
    }

    /**
     * Return all custom field definitions for a tenant + entity.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fieldsFor(string $tenantId, string $entity): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM tenant_fields WHERE tenant_id = :tenant_id AND entity = :entity'
        );
        $stmt->execute(['tenant_id' => $tenantId, 'entity' => $entity]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Remove a custom field definition by ID.
     */
    public function remove(int $id, string $tenantId): void
    {
        $this->pdo->prepare(
            'DELETE FROM tenant_fields WHERE id = :id AND tenant_id = :tenant_id'
        )->execute(['id' => $id, 'tenant_id' => $tenantId]);
    }

    /**
     * Validate custom field values against registered definitions.
     *
     * @param array<string, mixed>            $data   submitted custom field values
     * @param array<int, array<string, mixed>> $fields result of fieldsFor()
     * @throws \InvalidArgumentException
     */
    public function validate(array $data, array $fields): void
    {
        foreach ($fields as $field) {
            $name     = (string) $field['field_name'];
            $type     = (string) $field['field_type'];
            $required = (bool)   $field['required'];

            if ($required && !array_key_exists($name, $data)) {
                throw new \InvalidArgumentException("Custom field '{$name}' is required.");
            }

            if (array_key_exists($name, $data)) {
                $this->assertValueType($name, $data[$name], $type);
            }
        }
    }

    private function assertFieldName(string $name): void
    {
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $name)) {
            throw new \InvalidArgumentException("Invalid field name: '{$name}'");
        }
    }

    private function assertFieldType(string $type): void
    {
        if (!in_array($type, ['string', 'int', 'float', 'bool'], true)) {
            throw new \InvalidArgumentException("Unsupported field type: '{$type}'");
        }
    }

    private function assertValueType(string $name, mixed $value, string $type): void
    {
        $valid = match ($type) {
            'int'    => is_int($value),
            'float'  => is_float($value) || is_int($value),
            'bool'   => is_bool($value),
            'string' => is_string($value),
            default  => true,
        };

        if (!$valid) {
            throw new \InvalidArgumentException("Custom field '{$name}' must be of type {$type}.");
        }
    }
}

<?php

namespace EntityForge\Generator\Schema;

class SchemaValidator
{
    public function validate(array $config): void
    {
        // Entity name is required
        if (empty($config['entity'])) {
            throw new \InvalidArgumentException("Missing Required field 'entity' in config");
        }

        // Entity name must be valid class name
        if (!preg_match('/^[A-Z][A-Za-z0-9]*$/', $config['entity'])) {
            throw new \InvalidArgumentException("Invalid entity name: {$config['entity']}");
        }

        // Fields must be array if present
        if (isset($config['fields']) && !is_array($config['fields'])) {
            throw new \InvalidArgumentException("'fields' must be an object in config for entity");
        }

        // Field names validation
        foreach ($config['fields'] ?? [] as $field => $type) {
            if (!preg_match('/^[a-z_][a-z0-9_]*$/', $field)) {
                throw new \InvalidArgumentException("Invalid field name: {$field}");
            }

            if (!in_array($type, ['int', 'string', 'float', 'bool'], true)) {
                throw new \InvalidArgumentException("Unsupported field type: {$type} for {$field}");
            }
        }
    }
}
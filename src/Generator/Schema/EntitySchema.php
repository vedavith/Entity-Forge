<?php

namespace EntityForge\Generator\Schema;

class EntitySchema
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function getEntityName(): string
    {
        return $this->config['entity'];
    }

    public function isMultiTenant(): bool
    {
        return $this->config['multiTenant'] ?? false;
    }

    public function hasTimestamps(): bool
    {
        return $this->config['timestamps'] ?? false;
    }

    public function getPrimaryKey(): ?string
    {
        $fields = $this->config['fields'] ?? [];
        if (isset($fields['id'])) {
            return 'id';
        }
        $candidate = strtolower($this->config['entity']) . '_id';
        if (isset($fields[$candidate])) {
            return $candidate;
        }
        return null;
    }

public function getFields(): array
    {
        $fields = $this->config['fields'] ?? [];

        if ($this->isMultiTenant()) {
            $fields['tenant_id'] = 'string';
        }

        // Timestamp Support
        if ($this->hasTimestamps()) {
            $fields['created_at'] = 'string';
            $fields['updated_at'] = 'string';
        }

        return $fields;
    }

    public function getRelations(): array
    {
        return $this->config['relations'] ?? [];
    }

    public function getIndexes(): array
    {
        return $this->config['indexes'] ?? [];
    }
}
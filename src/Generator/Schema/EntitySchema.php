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

    public function getFields(): array
    {
        $fields = $this->config['fields'] ?? [];

        if ($this->isMultiTenant()) {
            $fields['tenant_id'] = 'string';
        }

        return $fields;
    }
}
<?php

namespace EntityForge\Generator\Schema;

class EntitySchema
{
    /** @var array<string, mixed> */
    private array $config;

    /** @param array<string, mixed> $config */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function getEntityName(): string
    {
        return $this->config['entity'];
    }

    public function hasTimestamps(): bool
    {
        return $this->config['timestamps'] ?? false;
    }

    public function getPrimaryKey(): string
    {
        return 'id';
    }

    /** @return array<string, string> */
    public function getFields(): array
    {
        $fields = $this->config['fields'] ?? [];

        if ($this->hasTimestamps()) {
            $fields['created_at'] = 'datetime';
            $fields['updated_at'] = 'datetime';
        }

        return $fields;
    }

    /** @return array<string, mixed> */
    public function getRelations(): array
    {
        return $this->config['relations'] ?? [];
    }

    /** @return array<int, mixed> */
    public function getIndexes(): array
    {
        return $this->config['indexes'] ?? [];
    }
}

<?php

namespace EntityForge\Generator\Builder;

use EntityForge\Generator\Schema\EntitySchema;

class MigrationBuilder
{
    public function buildUp(EntitySchema $schema): string
    {
        $table = strtolower($schema->getEntityName()) . 's';
        $fields = $schema->getFields();

        $columns = [];

        foreach ($fields as $name => $type) {
            $columns[] = $this->mapColumn($name, $type);
        }

        $columnsSql = implode(",\n    ", $columns);

        return <<<SQL
CREATE TABLE {$table} (
    {$columnsSql}
);
SQL;
    }

    public function buildDown(EntitySchema $schema): string
    {
        $table = strtolower($schema->getEntityName()) . 's';
        return "DROP TABLE IF EXISTS {$table};";
    }

    private function mapColumn(string $name, string $type): string
    {
        $sqlType = match ($type) {
            'int' => 'INT',
            'string' => 'VARCHAR(255)',
            'float' => 'FLOAT',
            'bool' => 'BOOLEAN',
            default => 'TEXT'
        };

        if ($name === 'id') {
            return "id INT PRIMARY KEY AUTO_INCREMENT";
        }

        return "{$name} {$sqlType}";
    }
}
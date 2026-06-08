<?php

namespace EntityForge\Generator\Builder;

use EntityForge\Generator\Schema\EntitySchema;

class MigrationBuilder
{
    /**
     * @param array<string, string> $pkMap  entity name → primary key column, used to resolve FK targets
     */
    public function buildUp(EntitySchema $schema, array $pkMap = []): string
    {
        $table     = strtolower($schema->getEntityName()) . 's';
        $fields    = $schema->getFields();
        $relations = $schema->getRelations();
        $indexes   = $schema->getIndexes();
        $pk        = $schema->getPrimaryKey();

        $definitions = [];

        foreach ($fields as $name => $type) {
            $definitions[] = $this->mapColumn($name, $type, $pk ?? '');
        }

        foreach ($relations['belongsTo'] ?? [] as $refEntity => $fkColumn) {
            $refTable   = strtolower($refEntity) . 's';
            $refPk      = $pkMap[$refEntity] ?? 'id';
            $constraint = "fk_{$table}_{$fkColumn}";
            $definitions[] = "CONSTRAINT {$constraint} FOREIGN KEY ({$fkColumn}) REFERENCES {$refTable}({$refPk})";
        }

        foreach ($indexes as $index) {
            $cols    = implode(', ', $index['columns']);
            $slug    = implode('_', $index['columns']);
            $unique  = $index['unique'] ?? false;
            $type    = $unique ? 'UNIQUE INDEX' : 'INDEX';
            $prefix  = $unique ? 'uix' : 'idx';
            $definitions[] = "{$type} {$prefix}_{$table}_{$slug} ({$cols})";
        }

        $body = implode(",\n    ", $definitions);

        return <<<SQL
CREATE TABLE {$table} (
    {$body}
);
SQL;
    }

    public function buildDown(EntitySchema $schema): string
    {
        $table = strtolower($schema->getEntityName()) . 's';
        return "DROP TABLE IF EXISTS {$table};";
    }

    private function mapColumn(string $name, string $type, string $pk = 'id'): string
    {
        $sqlType = match ($type) {
            'int' => 'INT',
            'string' => 'VARCHAR(255)',
            'float' => 'FLOAT',
            'bool' => 'BOOLEAN',
            default => 'TEXT'
        };

        if ($name === $pk) {
            return "{$pk} INT PRIMARY KEY AUTO_INCREMENT";
        }

        return "{$name} {$sqlType}";
    }
}
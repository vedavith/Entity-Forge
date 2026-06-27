<?php

namespace EntityForge\Generator\Builder;

use EntityForge\Generator\Schema\EntitySchema;
use EntityForge\Support\Str;

class MigrationBuilder
{
    /**
     * @param array<string, string> $pkMap     entity name → primary key column, used to resolve FK targets
     * @param string                $strategy  tenancy strategy: 'shared' adds tenant_id, 'database' omits it
     */
    public function buildUp(EntitySchema $schema, array $pkMap = [], string $strategy = 'shared'): string
    {
        $table     = Str::toTableName($schema->getEntityName());
        $fields    = $schema->getFields();
        $relations = $schema->getRelations();
        $indexes   = $schema->getIndexes();

        $fkColumns = array_values($relations['belongsTo'] ?? []);

        $definitions = ['id INT AUTO_INCREMENT PRIMARY KEY'];

        if ($strategy === 'shared') {
            $definitions[] = 'tenant_id VARCHAR(255) NOT NULL';
        }

        foreach ($fields as $name => $type) {
            if ($name === 'id' || $name === 'tenant_id' || in_array($name, $fkColumns, true)) {
                continue;
            }
            $definitions[] = $this->mapColumn($name, $type);
        }

        foreach ($relations['belongsTo'] ?? [] as $refEntity => $fkColumn) {
            $refTable   = Str::toTableName($refEntity);
            $refPk      = $pkMap[$refEntity] ?? 'id';
            $constraint = "fk_{$table}_{$fkColumn}";
            $definitions[] = "{$fkColumn} INT NOT NULL";
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
        $table = Str::toTableName($schema->getEntityName());
        return "DROP TABLE IF EXISTS {$table};";
    }

    private function mapColumn(string $name, string $type): string
    {
        $sqlType = match ($type) {
            'int'      => 'INT',
            'string'   => 'VARCHAR(255)',
            'float'    => 'FLOAT',
            'bool'     => 'BOOLEAN',
            'datetime' => 'DATETIME',
            default    => 'TEXT'
        };

        return "{$name} {$sqlType}";
    }
}

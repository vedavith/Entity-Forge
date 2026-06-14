<?php
namespace EntityForge\Generator\Builder;

use EntityForge\Generator\Schema\EntitySchema;

class EntityBuilder
{
    public function build(EntitySchema $schema): string
    {
        $className = $schema->getEntityName();
        $fields = $schema->getFields();

        $properties = '';
        foreach ($fields as $name => $type) {
            $properties .= "    public {$this->mapType($type)} \${$name};\n";
        }

        $relationsCode = $this->buildRelations($schema);
        $useBlock = $this->buildUseStatements($schema);

        $body = $properties . ($relationsCode !== '' ? "\n{$relationsCode}" : '');

        return <<<PHP
<?php

namespace App\Entity;
{$useBlock}
class {$className}
{
{$body}
}
PHP;
    }

    private function mapType(string $type): string
    {
        return match ($type) {
            'int'                    => 'int',
            'float'                  => 'float',
            'bool'                   => 'bool',
            'string', 'text', 'datetime' => 'string',
            default                  => 'mixed',
        };
    }

    private function buildUseStatements(EntitySchema $schema): string
    {
        $relations = $schema->getRelations();
        $entities = [];

        /** @var array<string, string> $belongsTo */
        $belongsTo = $relations['belongsTo'] ?? [];
        /** @var array<string, string> $hasMany */
        $hasMany = $relations['hasMany'] ?? [];

        foreach (array_keys($belongsTo) as $entity) {
            $entities[] = (string) $entity;
        }
        foreach (array_keys($hasMany) as $entity) {
            $entities[] = (string) $entity;
        }

        if (empty($entities)) {
            return "\n";
        }

        $lines = "\n";
        foreach (array_unique($entities) as $entity) {
            $lines .= "use App\\Entity\\{$entity};\n";
        }

        return $lines . "\n";
    }

    private function buildRelations(EntitySchema $schema): string
    {
        $relations = $schema->getRelations();
        $code = '';

        /** @var array<string, string> $belongsTo */
        $belongsTo = $relations['belongsTo'] ?? [];
        foreach ($belongsTo as $entity => $foreignKey) {
            $property = lcfirst((string) $entity);
            $code .= "    /** Loaded via {$foreignKey} */\n";
            $code .= "    public ?{$entity} \${$property} = null;\n";
        }

        /** @var array<string, string> $hasMany */
        $hasMany = $relations['hasMany'] ?? [];
        foreach ($hasMany as $entity => $foreignKey) {
            $property = $this->pluralize(lcfirst((string) $entity));
            $code .= "    /** @var {$entity}[] Loaded via {$foreignKey} */\n";
            $code .= "    public array \${$property} = [];\n";
        }

        return $code;
    }

    private function pluralize(string $word): string
    {
        if (str_ends_with($word, 'y') && !preg_match('/[aeiou]y$/i', $word)) {
            return substr($word, 0, -1) . 'ies';
        }
        if (preg_match('/(s|x|z|ch|sh)$/', $word)) {
            return $word . 'es';
        }
        return $word . 's';
    }
}
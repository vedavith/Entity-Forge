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

        //Get Relations
        $relationsCode = $this->buildRelations($schema);

        return <<<PHP
<?php

namespace App\Entity;

class {$className}
{
{$properties}

{$relationsCode}

}
PHP;
    }

    private function mapType(string $type): string
    {
        return match ($type) {
            'int' => 'int',
            'string' => 'string',
            default => 'mixed'
        };
    }

    private function buildRelations(EntitySchema $schema): string
    {
        $relations = $schema->getRelations();
        $code = '';

        // belongsTo
        foreach ($relations['belongsTo'] ?? [] as $entity => $foreignKey) {
            $method = lcfirst($entity);
            $code .= "
            public function {$method}(): string
                {
                return '{$entity} via {$foreignKey}';
                }
                ";
        }

        // hasMany
        foreach ($relations['hasMany'] ?? [] as $entity => $foreignKey) {
            $method = lcfirst($entity) . 's';
            $code .= "
        public function {$method}(): string
            {
            return '{$entity} list via {$foreignKey}';
            }";
        }

        return $code;
    }
}
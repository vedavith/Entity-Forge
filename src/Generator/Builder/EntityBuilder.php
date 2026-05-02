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

        return <<<PHP
<?php

class {$className}
{
{$properties}}
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
}
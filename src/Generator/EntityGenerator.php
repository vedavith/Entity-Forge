<?php

namespace EntityForge\Generator;

use EntityForge\Generator\Schema\EntitySchema;
use EntityForge\Generator\Builder\EntityBuilder;
use EntityForge\Generator\Builder\RepositoryBuilder;
use EntityForge\Generator\Writer\FileWriter;

class EntityGenerator
{
    public function generate(array $config): void
    {
        $schema = new EntitySchema($config);
        $entityCode = (new EntityBuilder())->build($schema);
        $repoCode = (new RepositoryBuilder())->build($schema);

        $writer = new FileWriter();

        $writer->write("output/{$schema->getEntityName()}.php", $entityCode);
        $writer->write("output/{$schema->getEntityName()}Repository.php", $repoCode);
    }
}
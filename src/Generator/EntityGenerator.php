<?php

namespace EntityForge\Generator;

use EntityForge\Generator\Schema\EntitySchema;
use EntityForge\Generator\Schema\SchemaValidator;
use EntityForge\Generator\Builder\EntityBuilder;
use EntityForge\Generator\Builder\RepositoryBuilder;
use EntityForge\Generator\Builder\MigrationBuilder;
use EntityForge\Generator\Writer\FileWriter;

class EntityGenerator
{
    private SchemaValidator $validator;
    private EntityBuilder $entityBuilder;
    private RepositoryBuilder $repositoryBuilder;
    private MigrationBuilder $migrationBuilder;
    private FileWriter $writer;

    // Shared counter for this generation session
    private int $migrationCounter = 0;

    public function __construct()
    {
        $this->validator = new SchemaValidator();
        $this->entityBuilder = new EntityBuilder();
        $this->repositoryBuilder = new RepositoryBuilder();
        $this->migrationBuilder = new MigrationBuilder();
        $this->writer = new FileWriter();
    }

    /**
     * @param array<string, string> $pkMap  entity name → primary key column for FK resolution
     */
    public function generate(array $config, bool $withMigration = false, array $pkMap = []): void
    {
        // Validate config
        $this->validator->validate($config);

        $schema = new EntitySchema($config);
        $entityName = $schema->getEntityName();

        // Generate code
        $entityCode = $this->entityBuilder->build($schema);
        $repositoryCode = $this->repositoryBuilder->build($schema);

        // Write entity + repository
        $this->writer->write("app/Entity/{$entityName}.php", $entityCode);
        $this->writer->write("app/Repository/{$entityName}Repository.php", $repositoryCode);

        // Generate migration
        if ($withMigration) {
            $baseName = $this->generateMigrationBaseName($entityName);

            $upSql = $this->migrationBuilder->buildUp($schema, $pkMap);
            $downSql = $this->migrationBuilder->buildDown($schema);

            $this->writer->write(
                "database/migrations/{$baseName}.up.sql",
                $upSql
            );

            $this->writer->write(
            "database/migrations/{$baseName}.down.sql",
                $downSql
            );
        }
    }

    private function generateMigrationBaseName(string $entity): string
    {
        $timestamp = date('Y_m_d_His');

        $this->migrationCounter++;

        $table = strtolower($entity) . 's';

        return sprintf(
            '%s_%04d_create_%s_table',
            $timestamp,
            $this->migrationCounter,
            $table
        );
    }
}
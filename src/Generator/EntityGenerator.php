<?php

namespace EntityForge\Generator;

use EntityForge\Generator\Schema\EntitySchema;
use EntityForge\Generator\Schema\SchemaValidator;
use EntityForge\Generator\Builder\EntityBuilder;
use EntityForge\Generator\Builder\RepositoryBuilder;
use EntityForge\Generator\Builder\MigrationBuilder;
use EntityForge\Generator\Writer\FileWriter;
use EntityForge\Support\Str;

class EntityGenerator
{
    private SchemaValidator $validator;
    private EntityBuilder $entityBuilder;
    private RepositoryBuilder $repositoryBuilder;
    private MigrationBuilder $migrationBuilder;
    private FileWriter $writer;

    private int $migrationCounter = 0;

    public function __construct()
    {
        $this->validator         = new SchemaValidator();
        $this->entityBuilder     = new EntityBuilder();
        $this->repositoryBuilder = new RepositoryBuilder();
        $this->migrationBuilder  = new MigrationBuilder();
        $this->writer            = new FileWriter();
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, string> $pkMap      entity name → primary key column for FK resolution
     * @param string                $strategy   tenancy strategy — passed through to MigrationBuilder
     */
    public function generate(
        array $config,
        bool $withMigration = false,
        array $pkMap = [],
        string $strategy = 'shared'
    ): void {
        $this->validator->validate($config);

        $schema     = new EntitySchema($config);
        $entityName = $schema->getEntityName();

        $this->writer->write("app/Entity/{$entityName}.php", $this->entityBuilder->build($schema));
        $this->writer->write("app/Repository/{$entityName}Repository.php", $this->repositoryBuilder->build($schema));

        if ($withMigration) {
            $baseName = $this->generateMigrationBaseName($entityName);

            $this->writer->write(
                "database/migrations/{$baseName}.up.sql",
                $this->migrationBuilder->buildUp($schema, $pkMap, $strategy)
            );
            $this->writer->write(
                "database/migrations/{$baseName}.down.sql",
                $this->migrationBuilder->buildDown($schema)
            );
        }
    }

    private function generateMigrationBaseName(string $entity): string
    {
        $this->migrationCounter++;

        return sprintf(
            '%s_%04d_create_%s_table',
            date('Y_m_d_His'),
            $this->migrationCounter,
            Str::toTableName($entity)
        );
    }
}

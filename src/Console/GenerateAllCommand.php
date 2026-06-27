<?php

namespace EntityForge\Console;

use EntityForge\Generator\EntityGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class GenerateAllCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('generate:all')
            ->setDescription('Generate all entities from config')
            ->addOption('migration', null, InputOption::VALUE_NONE, 'Generate migration')
            ->addOption('config-dir', null, InputOption::VALUE_OPTIONAL, 'Path to entity config directory', 'config/entities');
    }

    /**
     * Topological sort so referenced entities are generated before their dependents.
     * Throws if a circular dependency is detected.
     *
     * @param array<string, array<mixed>> $configs  keyed by entity name
     * @return array<string, array<mixed>>
     */
    private function sortByDependencies(array $configs): array
    {
        $deps = [];
        foreach ($configs as $name => $config) {
            $deps[$name] = array_keys($config['relations']['belongsTo'] ?? []);
        }

        $sorted = [];
        $visited = [];

        $visit = function (string $name) use (&$visit, &$sorted, &$visited, $configs, $deps): void {
            if (isset($visited[$name])) {
                if ($visited[$name] === 'pending') {
                    throw new \RuntimeException("Circular dependency detected involving entity: {$name}");
                }
                return;
            }

            $visited[$name] = 'pending';

            foreach ($deps[$name] ?? [] as $dep) {
                if (!isset($configs[$dep])) {
                    continue; // referenced entity not in this batch — skip
                }
                $visit((string) $dep);
            }

            $visited[$name] = 'done';
            $sorted[$name] = $configs[$name];
        };

        foreach (array_keys($configs) as $name) {
            $visit($name);
        }

        return $sorted;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configDir = $input->getOption('config-dir');

        if (!is_dir($configDir)) {
            $output->writeln("<error>Directory not found: {$configDir}</error>");
            return Command::FAILURE;
        }

        $files = glob($configDir . '/*.json');

        if (empty($files)) {
            $output->writeln("<comment>No entity configs found.</comment>");
            return Command::SUCCESS;
        }

        sort($files);

        $configs = [];
        foreach ($files as $file) {
            $config = json_decode((string) file_get_contents($file), true);
            if (!$config) {
                $output->writeln("<error>Invalid JSON: {$file}</error>");
                continue;
            }
            $configs[$config['entity']] = $config;
        }

        try {
            $configs = $this->sortByDependencies($configs);
        } catch (\RuntimeException $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            return Command::FAILURE;
        }

        $pkMap = array_fill_keys(array_keys($configs), 'id');

        $strategy = 'shared';
        $appYaml = getcwd() . '/config/application.yaml';
        if (file_exists($appYaml)) {
            $yaml = Yaml::parseFile($appYaml);
            $strategy = $yaml['tenancy']['strategy'] ?? 'shared';
        }

        $withMigration = $input->getOption('migration');

        // IMPORTANT: single generator instance
        $generator = new EntityGenerator();

        foreach ($configs as $entityName => $config) {
            try {
                $generator->generate($config, $withMigration, $pkMap, $strategy);
                $output->writeln("<info>Generated {$entityName}</info>");
            } catch (\Throwable $e) {
                $output->writeln("<error>{$e->getMessage()}</error>");
            }
        }

        return Command::SUCCESS;
    }
}
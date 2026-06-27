<?php


namespace EntityForge\Console;

use EntityForge\Generator\EntityGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class GenerateCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('generate')
            ->setDescription('Generate entity and repository')
            ->addArgument('entity', InputArgument::REQUIRED, 'Entity name')
            ->addOption('config', null, InputOption::VALUE_OPTIONAL, 'Config path', 'config/entities')
            ->addOption('migration', null, InputOption::VALUE_NONE, 'Generate migration');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $entity = $input->getArgument('entity');
        $configDir = $input->getOption('config');
        $withMigration = $input->getOption('migration');

        $configPath = "{$configDir}/{$entity}.json";

        if (!file_exists($configPath)) {
            $output->writeln("<error>Config not found: {$configPath}</error>");
            return Command::FAILURE;
        }

        $config = json_decode((string) file_get_contents($configPath), true);

        if (!$config) {
            $output->writeln("<error>Invalid JSON config</error>");
            return Command::FAILURE;
        }

        $strategy = 'shared';
        $appYaml = getcwd() . '/config/application.yaml';
        if (file_exists($appYaml)) {
            $yaml = Yaml::parseFile($appYaml);
            $strategy = $yaml['tenancy']['strategy'] ?? 'shared';
        }

        $generator = new EntityGenerator();

        try {
            $generator->generate($config, $withMigration, [], $strategy);
            $output->writeln("<info>Generated {$entity} successfully.</info>");
        } catch (\Throwable $e) {
            $output->writeln("<error>Error generating {$entity}: {$e->getMessage()}</error>");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
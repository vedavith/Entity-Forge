<?php

namespace EntityForge\Console;

use EntityForge\Generator\EntityGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateAllCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('generate:all')
            ->setDescription('Generate all entities from config')
            ->addOption('migration', null, InputOption::VALUE_NONE, 'Generate migration');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configDir = 'config/entities';

        if (!is_dir($configDir)) {
            $output->writeln("<error>Directory not found: {$configDir}</error>");
            return Command::FAILURE;
        }

        $files = glob($configDir . '/*.json');

        if (empty($files)) {
            $output->writeln("<comment>No entity configs found.</comment>");
            return Command::SUCCESS;
        }

        // Ensure deterministic order
        sort($files);

        $withMigration = $input->getOption('migration');

        // IMPORTANT: single generator instance
        $generator = new EntityGenerator();


        foreach ($files as $file) {
            $config = json_decode(file_get_contents($file), true);

            if (!$config) {
                $output->writeln("<error>Invalid JSON: {$file}</error>");
                continue;
            }

            try {
                $entityName = $config['entity'] ?? 'Unknown';

                $generator->generate($config, $withMigration);

                $output->writeln("<info>Generated {$entityName}</info>");
            } catch (\Throwable $e) {
                $output->writeln("<error>{$e->getMessage()}</error>");
            }
        }

        return Command::SUCCESS;
    }
}
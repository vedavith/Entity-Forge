<?php

namespace EntityForge\Console;

use EntityForge\Core\Application;
use EntityForge\Database\Connection;
use EntityForge\Database\MigrationRunner;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('migrate')
            ->setDescription('Run database migrations')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show pending migrations without executing them');
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $app = new Application(__DIR__ . '/../../config');
        $app->boot([], false);

        $config = $app->getConfig()['database'];

        $dryRun = (bool) $input->getOption('dry-run');
        $connection = new Connection($config);
        $runner = new MigrationRunner($connection);

        try {
            $runner->run('database/migrations', $dryRun);
            $output->writeln($dryRun
                ? '<comment>Dry run complete — no changes applied</comment>'
                : '<info>Migrations executed successfully</info>'
            );
        } catch (\Throwable $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
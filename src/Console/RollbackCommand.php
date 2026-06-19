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

class RollbackCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('migrate:rollback')
            ->setDescription('Rollback last batch')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be rolled back without executing');
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $app = new Application(getcwd() . '/config');
        $app->boot([], false);

        $db = $app->getConfig()['database'];

        $dryRun = (bool) $input->getOption('dry-run');
        $runner = new MigrationRunner(new Connection($db));

        try {
            $runner->rollback('database/migrations', $dryRun);
            $output->writeln($dryRun
                ? '<comment>Dry run complete — no changes applied</comment>'
                : '<info>Rollback successful</info>'
            );
        } catch (\Throwable $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
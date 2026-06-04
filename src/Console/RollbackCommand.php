<?php

namespace EntityForge\Console;

use EntityForge\Core\Application;
use EntityForge\Database\Connection;
use EntityForge\Database\MigrationRunner;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RollbackCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('migrate:rollback')
            ->setDescription('Rollback last batch');
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $app = new Application(__DIR__ . '/../../config');
        $app->boot([], false);

        $db = $app->getConfig()['database'];

        $runner = new MigrationRunner(new Connection($db));

        try {
            $runner->rollback('database/migrations');
            $output->writeln("<info>Rollback successful</info>");
        } catch (\Throwable $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
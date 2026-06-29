<?php

namespace EntityForge\Console;

use EntityForge\Core\Application;
use EntityForge\Tenant\TenantFieldRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FieldRemoveCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('field:remove')
            ->setDescription('Remove a custom field definition by ID')
            ->addArgument('id', InputArgument::REQUIRED, 'Field definition ID (from field:list)')
            ->addOption('tenant', null, InputOption::VALUE_REQUIRED, 'Tenant ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id       = (int)    $input->getArgument('id');
        $tenantId = (string) ($input->getOption('tenant') ?? '');

        if ($tenantId === '') {
            $output->writeln('<error>--tenant is required</error>');
            return Command::FAILURE;
        }

        $app = new Application(getcwd() . '/config');
        $app->boot([], false);

        $registry = new TenantFieldRegistry($app->getConfig());

        try {
            $registry->remove($id, $tenantId);
            $output->writeln("<info>Field #{$id} removed for tenant '{$tenantId}'</info>");
        } catch (\Throwable $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

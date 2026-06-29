<?php

namespace EntityForge\Console;

use EntityForge\Core\Application;
use EntityForge\Tenant\TenantFieldRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FieldAddCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('field:add')
            ->setDescription('Register a custom field for a tenant entity')
            ->addArgument('entity',     InputArgument::REQUIRED, 'Entity table name (e.g. accounts)')
            ->addArgument('field_name', InputArgument::REQUIRED, 'Field name (snake_case)')
            ->addArgument('type',       InputArgument::REQUIRED, 'Field type: string, int, float, bool')
            ->addOption('tenant',   null, InputOption::VALUE_REQUIRED, 'Tenant ID')
            ->addOption('label',    null, InputOption::VALUE_OPTIONAL, 'Human-readable label (defaults to field_name)')
            ->addOption('required', null, InputOption::VALUE_NONE,     'Mark field as required');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $entity    = (string) $input->getArgument('entity');
        $fieldName = (string) $input->getArgument('field_name');
        $type      = (string) $input->getArgument('type');
        $tenantId  = (string) ($input->getOption('tenant') ?? '');
        $label     = (string) ($input->getOption('label') ?? $fieldName);
        $required  = (bool)    $input->getOption('required');

        if ($tenantId === '') {
            $output->writeln('<error>--tenant is required</error>');
            return Command::FAILURE;
        }

        $app = new Application(getcwd() . '/config');
        $app->boot([], false);

        $registry = new TenantFieldRegistry($app->getConfig());

        try {
            $registry->register($tenantId, $entity, $fieldName, $type, $label, $required);
            $output->writeln("<info>Field '{$fieldName}' ({$type}) added to '{$entity}' for tenant '{$tenantId}'</info>");
        } catch (\Throwable $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

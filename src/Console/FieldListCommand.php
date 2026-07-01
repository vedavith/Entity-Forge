<?php

namespace EntityForge\Console;

use EntityForge\Core\Application;
use EntityForge\Tenant\TenantFieldRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FieldListCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('field:list')
            ->setDescription('List all custom fields registered for a tenant entity')
            ->addArgument('entity', InputArgument::REQUIRED, 'Entity table name (e.g. accounts)')
            ->addOption('tenant', null, InputOption::VALUE_REQUIRED, 'Tenant ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $entity   = (string) $input->getArgument('entity');
        $tenantId = (string) ($input->getOption('tenant') ?? '');

        if ($tenantId === '') {
            $output->writeln('<error>--tenant is required</error>');
            return Command::FAILURE;
        }

        $app = new Application(getcwd() . '/config');
        $app->boot([], false);

        $registry = new TenantFieldRegistry($app->getConfig());

        try {
            $fields = $registry->fieldsFor($tenantId, $entity);

            if (empty($fields)) {
                $output->writeln("<comment>No custom fields registered for '{$entity}' (tenant: {$tenantId})</comment>");
                return Command::SUCCESS;
            }

            $output->writeln("<info>Custom fields for '{$entity}' (tenant: {$tenantId}):</info>");
            $output->writeln('');

            foreach ($fields as $field) {
                $required = $field['required'] ? ' <comment>[required]</comment>' : '';
                $output->writeln(
                    "  <info>#{$field['id']}</info>  {$field['field_name']}  <comment>({$field['field_type']})</comment>  {$field['label']}{$required}"
                );
            }
        } catch (\Throwable $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

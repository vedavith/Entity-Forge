<?php

namespace EntityForge\Console;

use EntityForge\Core\Application;
use EntityForge\Tenant\TenantService;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TenantCreateCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('tenant:create')
            ->setDescription('Create and register a new tenant')
            ->addArgument('tenantId', InputArgument::REQUIRED, 'Tenant ID')
            ->addOption('name', null, InputOption::VALUE_OPTIONAL, 'Display name (defaults to tenantId)');
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tenantId = $input->getArgument('tenantId');
        $name = $input->getOption('name') ?? $tenantId;

        $app = new Application(getcwd() . '/config');
        $app->boot([], false);

        $service = new TenantService($app->getConfig());

        try {
            $service->onboard($tenantId, $name);
            $output->writeln("<info>Tenant '{$tenantId}' created and registered successfully</info>");
        } catch (\Throwable $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
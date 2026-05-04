<?php

namespace EntityForge\Console;

use EntityForge\Core\Application;
use EntityForge\Tenant\TenantProvisioner;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TenantCreateCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('tenant:create')
            ->setDescription('Create a new tenant')
            ->addArgument('tenantId', InputArgument::REQUIRED, 'Tenant ID');
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tenantId = $input->getArgument('tenantId');

        $app = new Application(__DIR__ . '/../../config');
        $app->boot([], false);

        $provisioner = new TenantProvisioner($app->getConfig());

        try {
            $provisioner->create($tenantId);

            $output->writeln("<info>Tenant {$tenantId} created successfully</info>");
        } catch (\Throwable $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
<?php

namespace EntityForge\Console;

use EntityForge\Core\Application;
use EntityForge\Database\Connection;
use EntityForge\Database\MigrationRunner;
use EntityForge\Tenant\TenantRepository;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateAllTenantsCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('migrate:all-tenants')
            ->setDescription('Run migrations against every registered tenant database (database strategy only)');
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $app = new Application(__DIR__ . '/../../config');
        $app->boot([], false);
        $config = $app->getConfig();

        if (($config['tenancy']['strategy'] ?? 'shared') !== 'database') {
            $output->writeln('<comment>migrate:all-tenants only applies to the database strategy.</comment>');
            return Command::SUCCESS;
        }

        $tenantRepo = new TenantRepository($config);
        $tenants = $tenantRepo->all();

        if (empty($tenants)) {
            $output->writeln('<comment>No tenants found.</comment>');
            return Command::SUCCESS;
        }

        $failed = 0;

        foreach ($tenants as $tenant) {
            $tenantId = $tenant['tenant_id'];
            $dbConfig = $config['database'];
            $dbConfig['database'] = $dbConfig['database'] . '_' . $tenantId;

            try {
                $connection = new Connection($dbConfig);
                $runner = new MigrationRunner($connection);
                $runner->run('database/migrations');
                $output->writeln("<info>Migrated: {$tenantId}</info>");
            } catch (\Throwable $e) {
                $output->writeln("<error>Failed {$tenantId}: {$e->getMessage()}</error>");
                $failed++;
            }
        }

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}

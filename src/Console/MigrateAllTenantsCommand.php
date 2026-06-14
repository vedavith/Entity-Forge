<?php

namespace EntityForge\Console;

use EntityForge\Core\Application;
use EntityForge\Database\Connection;
use EntityForge\Database\MigrationRunner;
use EntityForge\Tenant\TenantRepository;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class MigrateAllTenantsCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('migrate:all-tenants')
            ->setDescription('Run migrations against every active tenant database (database strategy only)')
            ->addOption('dry-run',  null, InputOption::VALUE_NONE,     'Show pending migrations per tenant without executing them')
            ->addOption('parallel', null, InputOption::VALUE_OPTIONAL,  'Number of concurrent workers',  5)
            ->addOption('tenant',   null, InputOption::VALUE_REQUIRED,  'Migrate a single tenant (used internally by parallel workers)');
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

        $dryRun = (bool) $input->getOption('dry-run');

        // Subprocess mode — migrate one tenant and exit
        if ($tenantId = $input->getOption('tenant')) {
            return $this->migrateTenant($tenantId, $config, $dryRun, $output);
        }

        // Orchestrator mode — spawn parallel subprocesses
        $tenantRepo = new TenantRepository($config);
        $tenants    = $tenantRepo->allActive();

        if (empty($tenants)) {
            $output->writeln('<comment>No tenants found.</comment>');
            return Command::SUCCESS;
        }

        $parallel = max(1, (int) $input->getOption('parallel'));
        $bin      = PHP_BINARY;
        $script   = __DIR__ . '/../../bin/ef';

        $queue    = array_column($tenants, 'tenant_id');
        $running  = [];
        $failed   = 0;

        while ($queue || $running) {
            // Fill pool up to $parallel
            while (count($running) < $parallel && $queue) {
                $id   = array_shift($queue);
                $args = [$bin, $script, 'migrate:all-tenants', '--tenant', $id];
                if ($dryRun) {
                    $args[] = '--dry-run';
                }
                $process = new Process($args);
                $process->start();
                $running[$id] = $process;
            }

            // Poll for finished processes
            foreach ($running as $id => $process) {
                if (!$process->isRunning()) {
                    $output->write($process->getOutput());
                    if (!$process->isSuccessful()) {
                        $output->writeln("<error>Failed {$id}: {$process->getErrorOutput()}</error>");
                        $failed++;
                    }
                    unset($running[$id]);
                }
            }

            if ($running) {
                usleep(50_000); // 50ms poll interval
            }
        }

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function migrateTenant(
        string $tenantId,
        array $config,
        bool $dryRun,
        OutputInterface $output
    ): int {
        $dbConfig             = $config['database'];
        $dbConfig['database'] = $dbConfig['database'] . '_' . $tenantId;

        try {
            $runner = new MigrationRunner(new Connection($dbConfig));
            $runner->run('database/migrations', $dryRun);
            $output->writeln($dryRun
                ? "<comment>Dry run: {$tenantId}</comment>"
                : "<info>Migrated: {$tenantId}</info>"
            );
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln("<error>Failed {$tenantId}: {$e->getMessage()}</error>");
            return Command::FAILURE;
        }
    }
}
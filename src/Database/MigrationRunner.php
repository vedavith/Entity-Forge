<?php

namespace EntityForge\Database;

use PDO;

class MigrationRunner
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function run(string $path, bool $dryRun = false): void
    {
        $pdo = $this->connection->getPdo();

        if (!$dryRun) {
            $this->ensureMigrationsTable();
        }

        $files = glob($path . '/*.up.sql') ?: [];
        sort($files);

        if (empty($files)) {
            echo "No migrations found.\n";
            return;
        }

        $executed = $dryRun ? $this->getExecutedSafe() : $this->getExecuted();
        $batch    = $dryRun ? 0 : $this->nextBatch();
        $prefix   = $dryRun ? '[DRY RUN] ' : '';

        foreach ($files as $file) {
            $name = basename($file);

            if (in_array($name, $executed, true)) {
                echo "{$prefix}Skipped (already executed): {$name}\n";
                continue;
            }

            if ($dryRun) {
                echo "{$prefix}Would execute: {$name}\n";
                continue;
            }

            $sql = file_get_contents($file);
            if ($sql === false) {
                throw new \Exception("Cannot read migration file: {$file}");
            }

            try {
                $pdo->exec($sql);
                $this->mark($name, $batch);
                echo "Executed: {$name}\n";
            } catch (\Throwable $e) {
                throw new \Exception("Migration failed: {$name} - " . $e->getMessage());
            }
        }

        echo $dryRun ? "{$prefix}Done (no changes applied)\n" : "✔ Done\n";
    }

    public function rollback(string $path, bool $dryRun = false): void
    {
        $pdo    = $this->connection->getPdo();
        $prefix = $dryRun ? '[DRY RUN] ' : '';
        $batch  = $this->lastBatch();

        if ($batch === 0) {
            echo "Nothing to rollback.\n";
            return;
        }

        $stmt = $pdo->prepare(
            "SELECT migration FROM migrations WHERE batch = :b ORDER BY id DESC"
        );
        $stmt->execute(['b' => $batch]);

        $migrations = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($migrations as $migration) {
            $down = $path . '/' . str_replace('.up.sql', '.down.sql', $migration);

            if (!file_exists($down)) {
                throw new \Exception("Missing down file: {$down}");
            }

            if ($dryRun) {
                echo "{$prefix}Would roll back: {$migration}\n";
                continue;
            }

            $sql = file_get_contents($down);
            if ($sql === false) {
                throw new \Exception("Cannot read rollback file: {$down}");
            }

            try {
                $pdo->exec($sql);

                $pdo->prepare("DELETE FROM migrations WHERE migration = :m")
                    ->execute(['m' => $migration]);

                echo "Rolled back: {$migration}\n";

            } catch (\Throwable $e) {
                throw new \Exception("Rollback failed: {$migration} - " . $e->getMessage());
            }
        }

        echo $dryRun ? "{$prefix}Done (no changes applied)\n" : "✔ Rollback complete\n";
    }

    private function ensureMigrationsTable(): void
    {
        $pdo = $this->connection->getPdo();

        $pdo->exec("
        CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255),
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

        $stmt = $pdo->query("SHOW COLUMNS FROM migrations LIKE 'batch'");
        if ($stmt === false) {
            throw new \Exception("Failed to check migrations table schema.");
        }
        $column = $stmt->fetch();

        if (!$column) {
            $pdo->exec("ALTER TABLE migrations ADD COLUMN batch INT DEFAULT 1");
        }
    }

    /**
     * @return array<int, string>
     */
    private function getExecuted(): array
    {
        $stmt = $this->connection->getPdo()->query("SELECT migration FROM migrations");
        if ($stmt === false) {
            throw new \Exception("Failed to query migrations table.");
        }
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * @return array<int, string>
     */
    private function getExecutedSafe(): array
    {
        try {
            return $this->getExecuted();
        } catch (\Throwable) {
            return [];
        }
    }

    private function mark(string $migration, int $batch): void
    {
        $stmt = $this->connection->getPdo()->prepare(
            "INSERT INTO migrations (migration, batch) VALUES (:m, :b)"
        );

        $stmt->execute([
            'm' => $migration,
            'b' => $batch
        ]);
    }

    private function nextBatch(): int
    {
        $stmt = $this->connection->getPdo()->query("SELECT MAX(batch) FROM migrations");
        if ($stmt === false) {
            throw new \Exception("Failed to query migration batch.");
        }
        return ((int) $stmt->fetchColumn()) + 1;
    }

    private function lastBatch(): int
    {
        $stmt = $this->connection->getPdo()->query("SELECT MAX(batch) FROM migrations");
        if ($stmt === false) {
            throw new \Exception("Failed to query migration batch.");
        }
        return (int) $stmt->fetchColumn();
    }
}

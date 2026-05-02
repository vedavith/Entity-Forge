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

    public function run(string $path): void
    {
        $pdo = $this->connection->getPdo();

        $this->ensureMigrationsTable();

        $files = glob($path . '/*.up.sql');
        sort($files);

        if (empty($files)) {
            echo "No migrations found.\n";
            return;
        }

        $executed = $this->getExecuted();
        $batch = $this->nextBatch();

        foreach ($files as $file) {
            $name = basename($file);

            if (in_array($name, $executed, true)) {
                echo "Skipped: {$name}\n";
                continue;
            }

            $sql = file_get_contents($file);

            try {
                $pdo->exec($sql);

                $this->mark($name, $batch);

                echo "Executed: {$name}\n";

            } catch (\Throwable $e) {
                throw new \Exception("Migration failed: {$name} - " . $e->getMessage());
            }
        }

        echo "✔ Done\n";
    }

    public function rollback(string $path): void
    {
        $pdo = $this->connection->getPdo();

        $batch = $this->lastBatch();

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

            $sql = file_get_contents($down);

            try {
                $pdo->exec($sql);

                $pdo->prepare("DELETE FROM migrations WHERE migration = :m")
                    ->execute(['m' => $migration]);

                echo "Rolled back: {$migration}\n";

            } catch (\Throwable $e) {
                throw new \Exception("Rollback failed: {$migration} - " . $e->getMessage());
            }
        }

        echo "✔ Rollback complete\n";
    }

    private function ensureMigrationsTable(): void
    {
        $pdo = $this->connection->getPdo();

        // Create table if not exists
        $pdo->exec("
        CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255),
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

        // Check if 'batch' column exists
        $stmt = $pdo->query("SHOW COLUMNS FROM migrations LIKE 'batch'");
        $column = $stmt->fetch();

        if (!$column) {
            $pdo->exec("ALTER TABLE migrations ADD COLUMN batch INT DEFAULT 1");
        }
    }

    private function getExecuted(): array
    {
        return $this->connection->getPdo()
            ->query("SELECT migration FROM migrations")
            ->fetchAll(PDO::FETCH_COLUMN);
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
        return ((int) $stmt->fetchColumn()) + 1;
    }

    private function lastBatch(): int
    {
        $stmt = $this->connection->getPdo()->query("SELECT MAX(batch) FROM migrations");
        return (int) $stmt->fetchColumn();
    }
}
<?php

namespace Tests\Database;

use EntityForge\Database\Connection;
use EntityForge\Database\MigrationRunner;
use Mockery;
use Mockery\MockInterface;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class MigrationRunnerTest extends TestCase
{
    private string $tmpDir;
    /** @var MockInterface&PDO */
    private MockInterface $pdo;
    /** @var MockInterface&Connection */
    private MockInterface $connection;
    private MigrationRunner $runner;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/ef_mig_' . uniqid();
        mkdir($this->tmpDir, 0755, true);

        $this->pdo = Mockery::mock(PDO::class);
        $this->connection = Mockery::mock(Connection::class);
        $this->connection->allows('getPdo')->andReturn($this->pdo);

        /** @var Connection $conn */
        $conn = $this->connection;
        $this->runner = new MigrationRunner($conn);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        $this->removeDir($this->tmpDir);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (glob($dir . '/*') as $item) {
            is_dir($item) ? $this->removeDir($item) : unlink($item);
        }
        rmdir($dir);
    }

    private function mockMigrationsTable(array $executed = [], int $maxBatch = 0): void
    {
        // ensureMigrationsTable: CREATE TABLE
        $this->pdo->allows('exec')->with(Mockery::pattern('/CREATE TABLE IF NOT EXISTS migrations/'))->once();

        // SHOW COLUMNS to check 'batch' exists
        $colStmt = Mockery::mock(PDOStatement::class);
        $colStmt->allows('fetch')->andReturn(['Field' => 'batch']);
        $this->pdo->allows('query')->with("SHOW COLUMNS FROM migrations LIKE 'batch'")->andReturn($colStmt);

        // getExecuted: SELECT migration FROM migrations
        $execStmt = Mockery::mock(PDOStatement::class);
        $execStmt->allows('fetchAll')->with(PDO::FETCH_COLUMN)->andReturn($executed);
        $this->pdo->allows('query')->with('SELECT migration FROM migrations')->andReturn($execStmt);

        // nextBatch: SELECT MAX(batch)
        $batchStmt = Mockery::mock(PDOStatement::class);
        $batchStmt->allows('fetchColumn')->andReturn($maxBatch);
        $this->pdo->allows('query')->with('SELECT MAX(batch) FROM migrations')->andReturn($batchStmt);
    }

    public function test_run_prints_no_migrations_when_dir_is_empty(): void
    {
        $this->mockMigrationsTable();

        $this->expectOutputString("No migrations found.\n");

        $this->runner->run($this->tmpDir);
    }

    public function test_run_executes_up_sql_file(): void
    {
        file_put_contents($this->tmpDir . '/0001_create_users.up.sql', 'CREATE TABLE users (id INT);');

        $this->mockMigrationsTable([], 0);

        $this->pdo->allows('exec')->with('CREATE TABLE users (id INT);')->once();

        $insertStmt = Mockery::mock(PDOStatement::class);
        $insertStmt->allows('execute')->once()->andReturn(true);
        $this->pdo->allows('prepare')
            ->with('INSERT INTO migrations (migration, batch) VALUES (:m, :b)')
            ->andReturn($insertStmt);

        $this->expectOutputRegex('/Executed: 0001_create_users\.up\.sql/');

        $this->runner->run($this->tmpDir);
    }

    public function test_run_skips_already_executed_migration(): void
    {
        file_put_contents($this->tmpDir . '/0001_create_users.up.sql', 'CREATE TABLE users (id INT);');

        $this->mockMigrationsTable(['0001_create_users.up.sql'], 1);

        $this->expectOutputRegex('/Skipped \(already executed\): 0001_create_users\.up\.sql/');

        $this->runner->run($this->tmpDir);
    }

    public function test_dry_run_shows_would_execute_without_writing(): void
    {
        file_put_contents($this->tmpDir . '/0001_create_users.up.sql', 'CREATE TABLE users (id INT);');

        // dry-run: no ensureMigrationsTable, getExecutedSafe falls back to [] on failure
        $execStmt = Mockery::mock(PDOStatement::class);
        $execStmt->allows('fetchAll')->with(PDO::FETCH_COLUMN)->andReturn([]);
        $this->pdo->allows('query')->with('SELECT migration FROM migrations')->andReturn($execStmt);

        // pdo->exec must NOT be called
        $this->pdo->shouldNotReceive('exec');

        $this->expectOutputRegex('/\[DRY RUN\] Would execute: 0001_create_users\.up\.sql/');

        $this->runner->run($this->tmpDir, true);
    }

    public function test_dry_run_marks_already_executed_as_skipped(): void
    {
        file_put_contents($this->tmpDir . '/0001_create_users.up.sql', 'CREATE TABLE users (id INT);');

        $execStmt = Mockery::mock(PDOStatement::class);
        $execStmt->allows('fetchAll')->with(PDO::FETCH_COLUMN)->andReturn(['0001_create_users.up.sql']);
        $this->pdo->allows('query')->with('SELECT migration FROM migrations')->andReturn($execStmt);

        $this->pdo->shouldNotReceive('exec');

        $this->expectOutputRegex('/\[DRY RUN\] Skipped \(already executed\): 0001_create_users\.up\.sql/');

        $this->runner->run($this->tmpDir, true);
    }

    public function test_dry_run_rollback_shows_would_roll_back_without_writing(): void
    {
        $migration = '0001_create_users.up.sql';
        file_put_contents($this->tmpDir . '/0001_create_users.down.sql', 'DROP TABLE users;');

        $batchStmt = Mockery::mock(PDOStatement::class);
        $batchStmt->allows('fetchColumn')->andReturn(1);
        $this->pdo->allows('query')->with('SELECT MAX(batch) FROM migrations')->andReturn($batchStmt);

        $listStmt = Mockery::mock(PDOStatement::class);
        $listStmt->allows('execute')->with(['b' => 1])->andReturn(true);
        $listStmt->allows('fetchAll')->with(PDO::FETCH_COLUMN)->andReturn([$migration]);
        $this->pdo->allows('prepare')
            ->with('SELECT migration FROM migrations WHERE batch = :b ORDER BY id DESC')
            ->andReturn($listStmt);

        $this->pdo->shouldNotReceive('exec');

        $this->expectOutputRegex('/\[DRY RUN\] Would roll back: 0001_create_users\.up\.sql/');

        $this->runner->rollback($this->tmpDir, true);
    }

    public function test_rollback_does_nothing_when_no_batches(): void
    {
        // rollback() calls lastBatch() only — no ensureMigrationsTable
        $batchStmt = Mockery::mock(PDOStatement::class);
        $batchStmt->allows('fetchColumn')->andReturn(0);
        $this->pdo->allows('query')->with('SELECT MAX(batch) FROM migrations')->andReturn($batchStmt);

        $this->expectOutputString("Nothing to rollback.\n");

        $this->runner->rollback($this->tmpDir);
    }

    public function test_run_throws_on_failed_migration(): void
    {
        file_put_contents($this->tmpDir . '/0001_bad.up.sql', 'INVALID SQL;');

        $this->mockMigrationsTable([], 0);

        $this->pdo->allows('exec')
            ->with('INVALID SQL;')
            ->andThrow(new \Exception('syntax error'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Migration failed: 0001_bad\.up\.sql/');

        $this->runner->run($this->tmpDir);
    }

    public function test_rollback_executes_down_sql_and_removes_record(): void
    {
        $migration = '0001_create_users.up.sql';
        $downFile  = $this->tmpDir . '/0001_create_users.down.sql';
        file_put_contents($downFile, 'DROP TABLE users;');

        // lastBatch → 1
        $batchStmt = Mockery::mock(PDOStatement::class);
        $batchStmt->allows('fetchColumn')->andReturn(1);
        $this->pdo->allows('query')->with('SELECT MAX(batch) FROM migrations')->andReturn($batchStmt);

        // SELECT migrations in batch
        $listStmt = Mockery::mock(PDOStatement::class);
        $listStmt->allows('execute')->with(['b' => 1])->andReturn(true);
        $listStmt->allows('fetchAll')->with(PDO::FETCH_COLUMN)->andReturn([$migration]);
        $this->pdo->allows('prepare')
            ->with('SELECT migration FROM migrations WHERE batch = :b ORDER BY id DESC')
            ->andReturn($listStmt);

        // execute .down.sql
        $this->pdo->allows('exec')->with('DROP TABLE users;')->once()->andReturn(0);

        // DELETE record
        $delStmt = Mockery::mock(PDOStatement::class);
        $delStmt->allows('execute')->with(['m' => $migration])->once()->andReturn(true);
        $this->pdo->allows('prepare')
            ->with('DELETE FROM migrations WHERE migration = :m')
            ->andReturn($delStmt);

        $this->expectOutputRegex('/Rolled back: 0001_create_users\.up\.sql/');

        $this->runner->rollback($this->tmpDir);
    }

    public function test_rollback_throws_when_down_file_missing(): void
    {
        $migration = '0001_missing.up.sql';

        $batchStmt = Mockery::mock(PDOStatement::class);
        $batchStmt->allows('fetchColumn')->andReturn(1);
        $this->pdo->allows('query')->with('SELECT MAX(batch) FROM migrations')->andReturn($batchStmt);

        $listStmt = Mockery::mock(PDOStatement::class);
        $listStmt->allows('execute')->andReturn(true);
        $listStmt->allows('fetchAll')->with(PDO::FETCH_COLUMN)->andReturn([$migration]);
        $this->pdo->allows('prepare')
            ->with('SELECT migration FROM migrations WHERE batch = :b ORDER BY id DESC')
            ->andReturn($listStmt);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Missing down file/');

        $this->runner->rollback($this->tmpDir);
    }

    public function test_rollback_throws_on_sql_failure(): void
    {
        $migration = '0001_fail.up.sql';
        file_put_contents($this->tmpDir . '/0001_fail.down.sql', 'BAD SQL;');

        $batchStmt = Mockery::mock(PDOStatement::class);
        $batchStmt->allows('fetchColumn')->andReturn(1);
        $this->pdo->allows('query')->with('SELECT MAX(batch) FROM migrations')->andReturn($batchStmt);

        $listStmt = Mockery::mock(PDOStatement::class);
        $listStmt->allows('execute')->andReturn(true);
        $listStmt->allows('fetchAll')->with(PDO::FETCH_COLUMN)->andReturn([$migration]);
        $this->pdo->allows('prepare')
            ->with('SELECT migration FROM migrations WHERE batch = :b ORDER BY id DESC')
            ->andReturn($listStmt);

        $this->pdo->allows('exec')
            ->with('BAD SQL;')
            ->andThrow(new \Exception('syntax error'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Rollback failed/');

        $this->runner->rollback($this->tmpDir);
    }
}

<?php

namespace Tests\Tenant;

use EntityForge\Database\Connection;
use EntityForge\Tenant\TenantRepository;
use Mockery;
use Mockery\MockInterface;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

class TenantRepositoryTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    private function dbConfig(): array
    {
        return [
            'database' => [
                'driver' => 'mysql', 'host' => 'localhost', 'port' => 3306,
                'database' => 'app', 'username' => 'root', 'password' => 'root',
            ],
        ];
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_create_inserts_tenant_row(): void
    {
        $stmt = Mockery::mock(PDOStatement::class);
        $stmt->allows('execute')
            ->with(['tenant_id' => 'acme', 'name' => 'Acme Corp'])
            ->once()
            ->andReturn(true);

        $pdo = Mockery::mock(PDO::class);
        $pdo->allows('prepare')
            ->with('INSERT INTO tenants (tenant_id, name) VALUES (:tenant_id, :name)')
            ->andReturn($stmt);

        /** @var MockInterface&Connection $conn */
        $conn = Mockery::mock('overload:' . Connection::class);
        $conn->allows('getPdo')->andReturn($pdo);

        $repo = new TenantRepository($this->dbConfig());
        $repo->create('acme', 'Acme Corp');

        $this->assertTrue(true);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_all_returns_tenant_rows(): void
    {
        $rows = [['id' => 1, 'tenant_id' => 'acme', 'name' => 'Acme']];

        $pdo = Mockery::mock(PDO::class);
        $stmt = Mockery::mock(PDOStatement::class);
        $stmt->allows('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn($rows);
        $pdo->allows('query')
            ->with('SELECT * FROM tenants')
            ->andReturn($stmt);

        /** @var MockInterface&Connection $conn */
        $conn = Mockery::mock('overload:' . Connection::class);
        $conn->allows('getPdo')->andReturn($pdo);

        $repo = new TenantRepository($this->dbConfig());

        $this->assertSame($rows, $repo->all());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_all_throws_when_query_returns_false(): void
    {
        $pdo = Mockery::mock(PDO::class);
        $pdo->allows('query')
            ->with('SELECT * FROM tenants')
            ->andReturn(false);

        /** @var MockInterface&Connection $conn */
        $conn = Mockery::mock('overload:' . Connection::class);
        $conn->allows('getPdo')->andReturn($pdo);

        $repo = new TenantRepository($this->dbConfig());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Failed to query tenants/');

        $repo->all();
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_all_active_returns_only_active_tenants(): void
    {
        $rows = [['id' => 1, 'tenant_id' => 'acme', 'name' => 'Acme', 'status' => 'active']];

        $stmt = Mockery::mock(PDOStatement::class);
        $stmt->allows('execute')->with(['status' => 'active'])->andReturn(true);
        $stmt->allows('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn($rows);

        $pdo = Mockery::mock(PDO::class);
        $pdo->allows('prepare')
            ->with('SELECT * FROM tenants WHERE status = :status')
            ->andReturn($stmt);

        /** @var MockInterface&Connection $conn */
        $conn = Mockery::mock('overload:' . Connection::class);
        $conn->allows('getPdo')->andReturn($pdo);

        $repo = new TenantRepository($this->dbConfig());

        $this->assertSame($rows, $repo->allActive());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_exists_returns_true_when_found(): void
    {
        $stmt = Mockery::mock(PDOStatement::class);
        $stmt->allows('execute')->with(['id' => 'acme'])->andReturn(true);
        $stmt->allows('fetchColumn')->andReturn(1);

        $pdo = Mockery::mock(PDO::class);
        $pdo->allows('prepare')
            ->with('SELECT COUNT(*) FROM tenants WHERE tenant_id = :id')
            ->andReturn($stmt);

        /** @var MockInterface&Connection $conn */
        $conn = Mockery::mock('overload:' . Connection::class);
        $conn->allows('getPdo')->andReturn($pdo);

        $repo = new TenantRepository($this->dbConfig());

        $this->assertTrue($repo->exists('acme'));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_exists_returns_false_when_not_found(): void
    {
        $stmt = Mockery::mock(PDOStatement::class);
        $stmt->allows('execute')->andReturn(true);
        $stmt->allows('fetchColumn')->andReturn(0);

        $pdo = Mockery::mock(PDO::class);
        $pdo->allows('prepare')->andReturn($stmt);

        /** @var MockInterface&Connection $conn */
        $conn = Mockery::mock('overload:' . Connection::class);
        $conn->allows('getPdo')->andReturn($pdo);

        $repo = new TenantRepository($this->dbConfig());

        $this->assertFalse($repo->exists('unknown'));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_find_by_tenant_id_returns_row_when_found(): void
    {
        $row = ['id' => 1, 'tenant_id' => 'acme', 'name' => 'Acme', 'status' => 'active'];

        $stmt = Mockery::mock(PDOStatement::class);
        $stmt->allows('execute')->with(['id' => 'acme'])->andReturn(true);
        $stmt->allows('fetch')->with(PDO::FETCH_ASSOC)->andReturn($row);

        $pdo = Mockery::mock(PDO::class);
        $pdo->allows('prepare')
            ->with('SELECT * FROM tenants WHERE tenant_id = :id LIMIT 1')
            ->andReturn($stmt);

        /** @var MockInterface&Connection $conn */
        $conn = Mockery::mock('overload:' . Connection::class);
        $conn->allows('getPdo')->andReturn($pdo);

        $repo = new TenantRepository($this->dbConfig());

        $this->assertSame($row, $repo->findByTenantId('acme'));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_find_by_tenant_id_returns_null_when_not_found(): void
    {
        $stmt = Mockery::mock(PDOStatement::class);
        $stmt->allows('execute')->andReturn(true);
        $stmt->allows('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false);

        $pdo = Mockery::mock(PDO::class);
        $pdo->allows('prepare')->andReturn($stmt);

        /** @var MockInterface&Connection $conn */
        $conn = Mockery::mock('overload:' . Connection::class);
        $conn->allows('getPdo')->andReturn($pdo);

        $repo = new TenantRepository($this->dbConfig());

        $this->assertNull($repo->findByTenantId('ghost'));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_update_status_executes_update(): void
    {
        $stmt = Mockery::mock(PDOStatement::class);
        $stmt->allows('execute')
            ->with(['status' => 'suspended', 'id' => 'acme'])
            ->once()
            ->andReturn(true);

        $pdo = Mockery::mock(PDO::class);
        $pdo->allows('prepare')
            ->with('UPDATE tenants SET status = :status WHERE tenant_id = :id')
            ->andReturn($stmt);

        /** @var MockInterface&Connection $conn */
        $conn = Mockery::mock('overload:' . Connection::class);
        $conn->allows('getPdo')->andReturn($pdo);

        $repo = new TenantRepository($this->dbConfig());
        $repo->updateStatus('acme', 'suspended');

        $this->assertTrue(true);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_delete_by_tenant_id_executes_delete(): void
    {
        $stmt = Mockery::mock(PDOStatement::class);
        $stmt->allows('execute')
            ->with(['id' => 'acme'])
            ->once()
            ->andReturn(true);

        $pdo = Mockery::mock(PDO::class);
        $pdo->allows('prepare')
            ->with('DELETE FROM tenants WHERE tenant_id = :id')
            ->andReturn($stmt);

        /** @var MockInterface&Connection $conn */
        $conn = Mockery::mock('overload:' . Connection::class);
        $conn->allows('getPdo')->andReturn($pdo);

        $repo = new TenantRepository($this->dbConfig());
        $repo->deleteByTenantId('acme');

        $this->assertTrue(true);
    }
}

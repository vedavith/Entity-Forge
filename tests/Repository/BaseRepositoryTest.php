<?php

namespace Tests\Repository;

use EntityForge\Database\Connection;
use EntityForge\Repository\BaseRepository;
use EntityForge\Tenant\TenantContext;
use Mockery;
use Mockery\MockInterface;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

/**
 * Concrete stub — table resolves to "widgets" (strips "Repository", lowercases, appends "s")
 */
class WidgetRepository extends BaseRepository {}

class BaseRepositoryTest extends TestCase
{
    /** @var MockInterface&PDO */
    private MockInterface $pdo;
    /** @var MockInterface&Connection */
    private MockInterface $connection;

    protected function setUp(): void
    {
        TenantContext::clear();
        $this->pdo = Mockery::mock(PDO::class);
        $this->connection = Mockery::mock(Connection::class);
        $this->connection->allows('getPdo')->andReturn($this->pdo);
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        Mockery::close();
    }

    private function config(string $strategy = 'shared'): array
    {
        return [
            'tenancy'  => ['strategy' => $strategy],
            'database' => [
                'driver'   => 'mysql',
                'host'     => 'localhost',
                'port'     => 3306,
                'database' => 'app',
                'username' => 'root',
                'password' => 'root',
            ],
        ];
    }

    private function repo(string $strategy = 'shared'): WidgetRepository
    {
        /** @var Connection $conn */
        $conn = $this->connection;

        $repo = new class($this->config($strategy), $conn) extends WidgetRepository {
            public function __construct(array $config, Connection $conn)
            {
                $this->config     = $config;
                $this->connection = $conn;
                $this->table      = 'widgets'; // bypass ReflectionClass on anonymous class
            }
        };

        /** @var WidgetRepository $repo */
        return $repo;
    }

    public function test_table_name_resolves_to_widgets(): void
    {
        $stmt = Mockery::mock(PDOStatement::class);
        $stmt->allows('execute')->with([])->andReturn(true);
        $stmt->allows('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([]);

        $this->pdo->allows('prepare')
            ->with('SELECT * FROM widgets')
            ->andReturn($stmt);

        $results = $this->repo('database')->findAll();

        $this->assertIsArray($results);
    }

    public function test_find_all_shared_strategy_scopes_by_tenant(): void
    {
        TenantContext::setTenantId('acme');

        $stmt = Mockery::mock(PDOStatement::class);
        $stmt->allows('execute')->with(['tenant_id' => 'acme'])->once()->andReturn(true);
        $stmt->allows('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([['id' => 1]]);

        $this->pdo->allows('prepare')
            ->with('SELECT * FROM widgets WHERE tenant_id = :tenant_id')
            ->andReturn($stmt);

        $results = $this->repo('shared')->findAll();

        $this->assertSame([['id' => 1]], $results);
    }

    public function test_find_all_database_strategy_no_tenant_scope(): void
    {
        $stmt = Mockery::mock(PDOStatement::class);
        $stmt->allows('execute')->with([])->once()->andReturn(true);
        $stmt->allows('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([]);

        $this->pdo->allows('prepare')
            ->with('SELECT * FROM widgets')
            ->andReturn($stmt);

        $results = $this->repo('database')->findAll();

        $this->assertSame([], $results);
    }

    public function test_find_by_id_shared_strategy_appends_tenant_clause(): void
    {
        TenantContext::setTenantId('acme');

        $stmt = Mockery::mock(PDOStatement::class);
        $stmt->allows('execute')->with(['id' => 5, 'tenant_id' => 'acme'])->once()->andReturn(true);
        $stmt->allows('fetch')->with(PDO::FETCH_ASSOC)->andReturn(['id' => 5, 'name' => 'Foo']);

        $this->pdo->allows('prepare')
            ->with('SELECT * FROM widgets WHERE id = :id AND tenant_id = :tenant_id')
            ->andReturn($stmt);

        $result = $this->repo('shared')->findById(5);

        $this->assertSame(['id' => 5, 'name' => 'Foo'], $result);
    }

    public function test_find_by_id_returns_null_when_not_found(): void
    {
        $stmt = Mockery::mock(PDOStatement::class);
        $stmt->allows('execute')->once()->andReturn(true);
        $stmt->allows('fetch')->with(PDO::FETCH_ASSOC)->andReturn(false);

        $this->pdo->allows('prepare')
            ->with('SELECT * FROM widgets WHERE id = :id')
            ->andReturn($stmt);

        $result = $this->repo('database')->findById(99);

        $this->assertNull($result);
    }

    public function test_create_shared_strategy_injects_tenant_id(): void
    {
        TenantContext::setTenantId('acme');

        $stmt = Mockery::mock(PDOStatement::class);
        $stmt->allows('execute')
            ->with(Mockery::on(fn($data) => isset($data['tenant_id']) && $data['tenant_id'] === 'acme'))
            ->once()
            ->andReturn(true);

        $this->pdo->allows('prepare')->andReturn($stmt);
        $this->pdo->allows('lastInsertId')->andReturn('42');

        $result = $this->repo('shared')->create(['name' => 'Alice']);

        $this->assertArrayHasKey('tenant_id', $result);
        $this->assertSame('acme', $result['tenant_id']);
        $this->assertSame(42, $result['id']);
    }

    public function test_create_database_strategy_does_not_inject_tenant_id(): void
    {
        $stmt = Mockery::mock(PDOStatement::class);
        $stmt->allows('execute')
            ->with(Mockery::on(fn($data) => !isset($data['tenant_id'])))
            ->once()
            ->andReturn(true);

        $this->pdo->allows('prepare')->andReturn($stmt);
        $this->pdo->allows('lastInsertId')->andReturn('7');

        $result = $this->repo('database')->create(['name' => 'Alice']);

        $this->assertArrayNotHasKey('tenant_id', $result);
        $this->assertSame(7, $result['id']);
    }

    public function test_update_shared_strategy_appends_tenant_clause(): void
    {
        TenantContext::setTenantId('acme');

        $stmt = Mockery::mock(PDOStatement::class);
        $stmt->allows('execute')->once()->andReturn(true);

        $this->pdo->allows('prepare')
            ->with('UPDATE widgets SET name = :name WHERE id = :id AND tenant_id = :tenant_id')
            ->andReturn($stmt);

        $result = $this->repo('shared')->update(1, ['name' => 'Bob']);

        $this->assertTrue($result);
    }

    public function test_delete_shared_strategy_appends_tenant_clause(): void
    {
        TenantContext::setTenantId('acme');

        $stmt = Mockery::mock(PDOStatement::class);
        $stmt->allows('execute')->once()->andReturn(true);

        $this->pdo->allows('prepare')
            ->with('DELETE FROM widgets WHERE id = :id AND tenant_id = :tenant_id')
            ->andReturn($stmt);

        $result = $this->repo('shared')->delete(1);

        $this->assertTrue($result);
    }

    public function test_where_shared_strategy_includes_tenant_scope(): void
    {
        TenantContext::setTenantId('acme');

        $stmt = Mockery::mock(PDOStatement::class);
        $stmt->allows('execute')
            ->with(['status' => 'active', 'tenant_id' => 'acme'])
            ->once()
            ->andReturn(true);
        $stmt->allows('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn([]);

        $this->pdo->allows('prepare')
            ->with('SELECT * FROM widgets WHERE status = :status AND tenant_id = :tenant_id')
            ->andReturn($stmt);

        $results = $this->repo('shared')->where(['status' => 'active']);

        $this->assertSame([], $results);
    }

    public function test_create_throws_on_invalid_column_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Invalid column name/');

        $this->repo('database')->create(['name; DROP TABLE widgets--' => 'bad']);
    }

    public function test_where_throws_on_invalid_column_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Invalid column name/');

        $this->repo('database')->where(['col name' => 'value']);
    }

    public function test_update_throws_on_invalid_column_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Invalid column name/');

        $this->repo('database')->update(1, ['`injected`' => 'value']);
    }

    public function test_resolve_table_name_derives_from_class_name(): void
    {
        $repo = (new \ReflectionClass(WidgetRepository::class))->newInstanceWithoutConstructor();
        $method = (new \ReflectionClass(BaseRepository::class))->getMethod('resolveTableName');
        $method->setAccessible(true);

        $this->assertSame('widgets', $method->invoke($repo));
    }

    public function test_transaction_methods_delegate_to_pdo(): void
    {
        $this->pdo->allows('beginTransaction')->once()->andReturn(true);
        $this->pdo->allows('commit')->once()->andReturn(true);
        $this->pdo->allows('rollBack')->once()->andReturn(true);

        $repo = $this->repo('database');
        $repo->beginTransaction();
        $repo->commit();
        $repo->rollback();

        $this->assertTrue(true);
    }
}

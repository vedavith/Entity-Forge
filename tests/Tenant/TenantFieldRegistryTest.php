<?php

namespace Tests\Tenant;

use EntityForge\Database\Connection;
use EntityForge\Tenant\TenantFieldRegistry;
use Mockery;
use Mockery\MockInterface;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

class TenantFieldRegistryTest extends TestCase
{
    /** @var MockInterface&PDO */
    private MockInterface $pdo;

    protected function setUp(): void
    {
        $this->pdo = Mockery::mock(PDO::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    private function config(): array
    {
        return [
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

    private function registry(): TenantFieldRegistry
    {
        /** @var MockInterface&Connection $conn */
        $conn = Mockery::mock('overload:' . Connection::class);
        $conn->allows('getPdo')->andReturn($this->pdo);

        return new TenantFieldRegistry($this->config());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_register_inserts_field_definition(): void
    {
        $stmt = Mockery::mock(PDOStatement::class);
        $stmt->allows('execute')
            ->with(Mockery::on(fn($p) =>
                $p['tenant_id']  === 'beta'       &&
                $p['entity']     === 'accounts'   &&
                $p['field_name'] === 'vat_number' &&
                $p['field_type'] === 'string'     &&
                $p['label']      === 'VAT Number' &&
                $p['required']   === 0
            ))
            ->once()
            ->andReturn(true);

        $this->pdo->allows('prepare')
            ->with(Mockery::pattern('/INSERT INTO tenant_fields/'))
            ->andReturn($stmt);

        $this->registry()->register('beta', 'accounts', 'vat_number', 'string', 'VAT Number');

        $this->assertTrue(true);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_register_throws_on_invalid_field_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Invalid field name/');

        $conn = Mockery::mock('overload:' . Connection::class);
        $conn->allows('getPdo')->andReturn($this->pdo);

        (new TenantFieldRegistry($this->config()))
            ->register('beta', 'accounts', 'Bad Name', 'string', 'Bad');
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_register_throws_on_unsupported_field_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Unsupported field type/');

        $conn = Mockery::mock('overload:' . Connection::class);
        $conn->allows('getPdo')->andReturn($this->pdo);

        (new TenantFieldRegistry($this->config()))
            ->register('beta', 'accounts', 'vat_number', 'array', 'VAT');
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_fields_for_returns_tenant_scoped_definitions(): void
    {
        $rows = [
            ['id' => 1, 'tenant_id' => 'beta', 'entity' => 'accounts', 'field_name' => 'vat_number', 'field_type' => 'string', 'label' => 'VAT Number', 'required' => 0],
        ];

        $stmt = Mockery::mock(PDOStatement::class);
        $stmt->allows('execute')->with(['tenant_id' => 'beta', 'entity' => 'accounts'])->once()->andReturn(true);
        $stmt->allows('fetchAll')->with(PDO::FETCH_ASSOC)->andReturn($rows);

        $this->pdo->allows('prepare')
            ->with('SELECT * FROM tenant_fields WHERE tenant_id = :tenant_id AND entity = :entity')
            ->andReturn($stmt);

        $result = $this->registry()->fieldsFor('beta', 'accounts');

        $this->assertSame($rows, $result);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_remove_deletes_by_id_and_tenant(): void
    {
        $stmt = Mockery::mock(PDOStatement::class);
        $stmt->allows('execute')->with(['id' => 3, 'tenant_id' => 'beta'])->once()->andReturn(true);

        $this->pdo->allows('prepare')
            ->with('DELETE FROM tenant_fields WHERE id = :id AND tenant_id = :tenant_id')
            ->andReturn($stmt);

        $this->registry()->remove(3, 'beta');

        $this->assertTrue(true);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_validate_passes_for_valid_data(): void
    {
        $conn = Mockery::mock('overload:' . Connection::class);
        $conn->allows('getPdo')->andReturn($this->pdo);

        $registry = new TenantFieldRegistry($this->config());

        $fields = [
            ['field_name' => 'vat_number', 'field_type' => 'string', 'required' => true],
        ];

        $registry->validate(['vat_number' => 'BE0123'], $fields);

        $this->assertTrue(true);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_validate_throws_when_required_field_missing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches("/Custom field 'vat_number' is required/");

        $conn = Mockery::mock('overload:' . Connection::class);
        $conn->allows('getPdo')->andReturn($this->pdo);

        $registry = new TenantFieldRegistry($this->config());

        $fields = [
            ['field_name' => 'vat_number', 'field_type' => 'string', 'required' => true],
        ];

        $registry->validate([], $fields);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_validate_throws_on_type_mismatch(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches("/must be of type int/");

        $conn = Mockery::mock('overload:' . Connection::class);
        $conn->allows('getPdo')->andReturn($this->pdo);

        $registry = new TenantFieldRegistry($this->config());

        $fields = [
            ['field_name' => 'fiscal_year', 'field_type' => 'int', 'required' => false],
        ];

        $registry->validate(['fiscal_year' => 'not-an-int'], $fields);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_validate_optional_field_absent_passes(): void
    {
        $conn = Mockery::mock('overload:' . Connection::class);
        $conn->allows('getPdo')->andReturn($this->pdo);

        $registry = new TenantFieldRegistry($this->config());

        $fields = [
            ['field_name' => 'vat_number', 'field_type' => 'string', 'required' => false],
        ];

        $registry->validate([], $fields);

        $this->assertTrue(true);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_validate_passes_for_float_value(): void
    {
        $conn = Mockery::mock('overload:' . Connection::class);
        $conn->allows('getPdo')->andReturn($this->pdo);

        $registry = new TenantFieldRegistry($this->config());

        $fields = [['field_name' => 'rate', 'field_type' => 'float', 'required' => false]];
        $registry->validate(['rate' => 1.5], $fields);

        $this->assertTrue(true);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_validate_passes_for_bool_value(): void
    {
        $conn = Mockery::mock('overload:' . Connection::class);
        $conn->allows('getPdo')->andReturn($this->pdo);

        $registry = new TenantFieldRegistry($this->config());

        $fields = [['field_name' => 'active', 'field_type' => 'bool', 'required' => false]];
        $registry->validate(['active' => true], $fields);

        $this->assertTrue(true);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_validate_unknown_type_passes_without_error(): void
    {
        $conn = Mockery::mock('overload:' . Connection::class);
        $conn->allows('getPdo')->andReturn($this->pdo);

        $registry = new TenantFieldRegistry($this->config());

        $fields = [['field_name' => 'misc', 'field_type' => 'unknown', 'required' => false]];
        $registry->validate(['misc' => 'anything'], $fields);

        $this->assertTrue(true);
    }
}

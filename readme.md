# EntityForge

**EntityForge** is a configuration-driven, multi-tenant SaaS framework built in PHP 8.4.

It provides everything needed to build a scalable SaaS backend: JSON-driven code generation, two tenant isolation strategies, automated migrations, an HTTP routing layer with middleware pipeline, and a dependency injection container — all wired together through a single boot cycle.

---

## Features

- **Code generation** from JSON schemas — entities, repositories, and migrations in one command; supports field types, foreign key relations, and composite indexes
- **Two tenancy strategies** — shared database (scoped by `tenant_id`) or database-per-tenant
- **Tenant lifecycle management** — onboard, suspend, resume, offboard via `TenantService`
- **HTTP layer** — `Router` (backed by FastRoute), immutable `Pipeline`, immutable `Request`/`Response` value objects
- **Middleware pipeline** — composable, immutable, executed outermost-first
- **DI container** — bind, singleton, instance, and reflection-based autowire
- **Migration system** — forward and rollback with dry-run, batch-tracked, tenant-aware
- **Multi-worker safe** — `RequestLifecycle` prevents tenant state leaking between requests in long-lived workers (Swoole, RoadRunner, Octane)

---

## Requirements

- PHP 8.4+
- MySQL (PDO)
- Composer

---

## Installation

```bash
composer require entity-forge/entity-forge
```

> Package publication to Packagist is pending. Clone the repo and run `composer install` to use locally.

---

## Quick Start

### 1. Configure tenancy

`config/application.yaml`:

```yaml
tenancy:
  enabled: true
  strategy: shared        # or: database
  resolver: header        # or: subdomain | jwt
  header_key: X-Tenant-ID
  # resolver: jwt
  # jwt_public_key: /path/to/public.pem
  # jwt_algorithm: RS256
  # jwt_tenant_claim: tenant_id

database:
  driver: mysql
  host: 127.0.0.1
  port: 3306
  database: entity_forge
  username: root
  password: root
```

### 2. Define an entity

`config/entities/Order.json`:

```json
{
  "entity": "Order",
  "fields": { "amount": "float", "status": "string" },
  "relations": {
    "belongsTo": { "User": "user_id" }
  },
  "indexes": [
    { "columns": ["status"] },
    { "columns": ["user_id", "status"], "unique": true }
  ]
}
```

`relations.belongsTo` emits a `CONSTRAINT fk_… FOREIGN KEY` clause. `indexes` emits `INDEX` or `UNIQUE INDEX` clauses. Both are optional.

### 3. Generate and migrate

```bash
php bin/ef generate Order --migration
php bin/ef migrate
```

### 4. Onboard a tenant (database strategy)

```bash
php bin/ef tenant:create acme --name "Acme Corp"
```

Or programmatically — this also registers the tenant in the `tenants` table:

```php
$app->getContainer()->make(TenantService::class)->onboard('acme', 'Acme Corp');
```

### 5. Boot and query

```php
use EntityForge\Core\Application;
use App\Repository\OrderRepository;

$app = new Application(__DIR__ . '/config');
$app->boot(['headers' => ['X-Tenant-ID' => 'acme']], true);

$repo = new OrderRepository($app->getConfig());
$repo->create(['amount' => 99.00, 'status' => 'pending']);
print_r($repo->findAll());
```

### 6. Handle an HTTP request

```php
use EntityForge\Http\{Router, Pipeline, Request, Response};

$router = new Router();
$router->get('/orders',       fn(Request $req): Response => (new Response())->withJson($repo->findAll()));
$router->get('/orders/{id}',  fn(Request $req): Response => (new Response())->withJson($repo->findById((int) $req->param('id'))));
$router->post('/orders',      fn(Request $req): Response => (new Response())->withJson($repo->create(['amount' => $req->body('amount'), 'status' => 'pending']), 201));

$pipeline = (new Pipeline())
    ->pipe(new AuthMiddleware())
    ->pipe(new TenantMiddleware());

$response = $pipeline->run(Request::capture(), fn(Request $req): Response => $router->dispatch($req));
$response->send();
```

---

## CLI Reference

| Command | Options | Description |
|---|---|---|
| `generate <Entity>` | `--migration` | Generate entity + repository from JSON schema |
| `generate:all` | `--config-dir` | Generate all schemas in `config/entities/` |
| `migrate` | `--dry-run` | Run pending migrations on the main database |
| `migrate:rollback` | `--dry-run` | Roll back the last migration batch |
| `migrate:all-tenants` | `--tenant <id>`, `--parallel N`, `--dry-run` | Run pending migrations on every active tenant DB |
| `tenant:create <id>` | `--name` | Onboard a new tenant |

`generate:all` uses a single `EntityGenerator` instance to guarantee monotonically ordered migration timestamps within a session.

`migrate:all-tenants` spawns up to `--parallel N` (default 5) concurrent worker processes via `symfony/process`. Suspended tenants are skipped. A per-tenant failure is reported but does not stop other tenants from being migrated.

---

## Tenancy Strategies

The pivot is `tenancy.strategy` in `config/application.yaml`.

### `shared` — single database, tenant_id column

Every table has a `tenant_id` column. `BaseRepository` automatically appends `WHERE tenant_id = :tenant_id` (and `AND tenant_id = :tenant_id` on writes) to every query.

```
entity_forge
  ├── tenants          ← registry
  └── orders           ← tenant_id = 'acme' | 'corp' | ...
```

### `database` — one database per tenant

Each tenant gets its own database named `{base_db}_{tenantId}`. `TenantConnectionResolver` selects the correct connection. No `tenant_id` column needed.

```
entity_forge            ← main DB: tenants registry only
entity_forge_acme       ← tenant DB: all application data
entity_forge_corp       ← tenant DB: all application data
```

---

## Tenant Resolution

Configure via `tenancy.resolver` in `application.yaml`:

| Resolver | Config keys | How it works |
|---|---|---|
| `header` | `header_key` (default: `X-Tenant-ID`) | Reads the named HTTP header from the request context |
| `subdomain` | `subdomain_depth`, `subdomain_min_parts` (default: 3) | Extracts the leading subdomain from the `host` context key (`acme.example.com` → `acme`). Set `subdomain_min_parts: 2` for two-part hosts like `acme.io` |
| `jwt` | `jwt_public_key`, `jwt_algorithm` (default: `RS256`), `jwt_tenant_claim` (default: `tenant_id`) | Decodes and verifies a Bearer JWT from the `Authorization` header, then extracts the named claim |

Add custom resolvers by implementing `TenantResolverInterface` and registering them in `TenantResolverFactory`.

---

## Tenant Lifecycle

`TenantService` is the canonical entry point for tenant operations. It is pre-registered as a singleton in the DI container after `boot()`.

```php
$svc = $app->getContainer()->make(TenantService::class);

$svc->onboard('acme', 'Acme Corp');  // validates ID, provisions DB, runs migrations, registers tenant
$svc->suspend('acme');               // sets status = 'suspended'; blocks future boots
$svc->resume('acme');                // sets status = 'active'
$svc->offboard('acme');              // drops DB (database strategy) + removes tenant record
```

`onboard()` rejects tenant IDs that do not match `^[a-zA-Z0-9_-]+$`.

`TenantProvisioner::create()` rolls back atomically on failure — if migrations fail after the database was created, the database is dropped before re-throwing. No orphaned databases.

Suspended tenants are blocked at `Application::boot()` — `assertTenantActive()` throws before any repository is instantiated.

---

## HTTP Layer

### Router

Backed by `nikic/fast-route`. Supports `{name}` parameter segments. Register exact paths before parameterised ones — routes match in registration order.

```php
$router = new Router();
$router->get('/users',         fn(Request $req): Response => ...);
$router->get('/users/{id}',    fn(Request $req): Response => ... $req->param('id') ...);
$router->post('/users',        fn(Request $req): Response => ...);
$router->put('/users/{id}',    fn(Request $req): Response => ...);
$router->delete('/users/{id}', fn(Request $req): Response => ...);

$response = $router->dispatch($request);  // returns 404 or 405 automatically
```

### Request

Immutable value object. Constructed directly or captured from PHP superglobals:

```php
$request = new Request(headers: [...], query: [...], body: [...], method: 'POST', path: '/users');
$request = Request::capture();  // reads $_SERVER, $_GET, $_POST, getallheaders()

$request->header('X-Tenant-ID');
$request->query('page');
$request->body('name');
$request->method();      // 'GET', 'POST', ...
$request->path();        // '/users/42'
$request->param('id');   // route parameter injected by Router
$request->params();      // all route parameters as array
```

### Response

Three output modes:

```php
// Immutable builder — standard pipeline path
$response = (new Response())
    ->withJson(['id' => 1], 201)
    ->withHeader('X-Request-Id', $id);
$response->send();   // http_response_code + headers + echo body

// Streaming — caller controls chunk output and flush timing
(new Response())
    ->withStatus(200)
    ->withHeader('Content-Type', 'text/csv')
    ->stream(function (): void {
        echo "id,name\n";
        flush();
    });

// Legacy direct-echo (kept for backwards compatibility)
(new Response())->json(['ok' => true], 200);
```

### Middleware Pipeline

Immutable chain — each `pipe()` call returns a new instance. Executed outermost-first.

```php
interface MiddlewareInterface {
    public function handle(Request $request, callable $next): Response;
}

$pipeline = (new Pipeline())
    ->pipe(new LoggingMiddleware())
    ->pipe(new AuthMiddleware());

$response = $pipeline->run($request, fn(Request $req): Response => $router->dispatch($req));
```

---

## DI Container

```php
$container = $app->getContainer();

$container->bind(MyService::class, fn($c) => new MyService($c->make(Dep::class)));  // new instance per call
$container->singleton(Cache::class, fn() => new RedisCache());                      // shared instance
$container->instance(Config::class, $myConfig);                                      // pre-built object

$service = $container->make(MyService::class);
```

Unregistered classes are resolved automatically via reflection. All constructor parameters must be typed class parameters or have default values; otherwise `make()` throws `InvalidArgumentException`.

---

## Repository Layer

All generated repositories extend `BaseRepository` and inherit:

```php
public function create(array $data): array
public function findAll(): array
public function findById(int $id): ?array
public function where(array $conditions): array
public function update(int $id, array $data): bool
public function delete(int $id): bool

public function beginTransaction(): void
public function commit(): void
public function rollback(): void
```

Column names passed to `create()`, `where()`, and `update()` are validated against `^[a-zA-Z0-9_]+$` before SQL interpolation. `InvalidArgumentException` is thrown on violation.

The table name is derived from the class name (`OrderRepository` → `orders`). Set `$this->table` in the subclass constructor to override.

Never reuse a repository instance across tenant switches — instantiate a fresh one after `TenantContext::setTenantId()`.

---

## Migration System

```
database/migrations/
  20260101_000001_create_orders_table.up.sql
  20260101_000001_create_orders_table.down.sql
```

Every `.up.sql` must have a paired `.down.sql`. A missing down file aborts rollback with an exception. `MigrationRunner` skips already-executed files based on the `migrations` tracking table (auto-created).

Both `run()` and `rollback()` accept a dry-run mode — all writes are skipped and output is prefixed with `[DRY RUN]`:

```bash
php bin/ef migrate --dry-run
php bin/ef migrate:rollback --dry-run
```

---

## Long-Lived Workers

`TenantContext` is a static singleton. In PHP-FPM static state resets per process. In persistent runtimes (Swoole, RoadRunner, Octane), it persists between requests.

`TenantContext::setTenantId()` throws `LogicException` if a tenant ID is already set — a forgotten `RequestLifecycle::begin()` call surfaces as a hard error rather than a silent data leak.

Wrap each request loop iteration:

```php
RequestLifecycle::begin();   // clears TenantContext + flushes connection cache

// ... handle request ...

RequestLifecycle::end();     // clears again on teardown
```

---

## Boot Sequence

```
Application::boot($context, $resolveTenant)
  │
  ├── ConfigLoader::loadMultiple([saas.yaml, application.yaml])
  │     array_replace_recursive — application.yaml wins on conflicts
  │
  ├── ConfigValidator::validate()
  │     requires: tenancy.enabled, database.{driver,host,port,database,username,password}
  │
  ├── CoreSchemaManager::ensure()
  │     CREATE TABLE IF NOT EXISTS tenants  (always, both strategies)
  │
  ├── Container::registerBindings()
  │     singletons: TenantRepository, TenantProvisioner, TenantService
  │
  └── if $resolveTenant && tenancy.enabled:
        TenantResolverFactory::create() → resolver.resolve($context)
        TenantContext::setTenantId()    ← throws LogicException if already set
        if strategy === database:
          TenantRepository::findByTenantId() — throws if not found or suspended
```

Pass `false` as the second argument to skip tenant resolution — required for CLI commands that run before a tenant is set.

---

## Key Invariants

1. **Tenant isolation is never optional.** Every query decision must account for both strategies.
2. **Main DB ↔ tenant DB boundary is sacred.** The `tenants` registry lives only in the main DB. Application data lives only in tenant DBs (or is scoped by `tenant_id` in shared mode).
3. **Repository instances are not reusable across tenant switches.** Instantiate fresh after `TenantContext::setTenantId()`.
4. **Idempotent infrastructure.** `CREATE TABLE IF NOT EXISTS`, batch-tracked migrations, `CoreSchemaManager` — follow this pattern for all schema management.
5. **Explicit over implicit.** Tenant resolution, connection selection, and scope injection are always conscious calls.
6. **Configuration drives generation.** New entity types go through the generator pipeline, not handwritten files.

---

## Running Tests

```bash
composer install
vendor/bin/phpunit
vendor/bin/phpunit tests/Path/To/SomeTest.php   # single file
```

---

## Roadmap

- [ ] Session-based tenant resolver
- [ ] Artisan-style scaffolding for middleware and controllers
- [ ] Official Packagist release

---

## Contributing

Contributions are welcome. Open an issue or pull request on GitHub.

---

## License

MIT

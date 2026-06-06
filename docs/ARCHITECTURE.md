# EntityForge Architecture

## Overview

EntityForge is a configuration-driven, multi-tenant SaaS framework built in PHP 8.4. It provides:

- Multi-tenant data isolation (shared-table and database-per-tenant strategies)
- Configuration-driven entity, repository, and migration generation
- Batch-tracked migration system with rollback
- HTTP request/response pipeline with middleware support
- Tenant lifecycle management (onboard, suspend, resume, offboard)

---

## System Map

```
src/
├── Config/
│   ├── ConfigLoader.php          — merges YAML config files
│   └── ConfigValidator.php       — validates required keys before boot
│
├── Console/
│   ├── GenerateCommand.php       — generate single entity from JSON schema
│   ├── GenerateAllCommand.php    — generate all entities in config/entities/
│   ├── MigrateCommand.php        — run pending migrations (main DB)
│   ├── MigrateAllTenantsCommand.php — run migrations on every tenant DB
│   ├── RollbackCommand.php       — rollback last migration batch
│   └── TenantCreateCommand.php   — onboard a new tenant via TenantService
│
├── Core/
│   ├── Application.php           — boot entry point
│   ├── Container.php             — DI container with auto-wiring
│   └── CoreSchemaManager.php     — creates tenants table on every boot
│
├── Database/
│   ├── Connection.php            — PDO wrapper
│   └── MigrationRunner.php       — runs/tracks/rolls back SQL migrations
│
├── Generator/
│   ├── EntityGenerator.php
│   ├── Builder/                  — EntityBuilder, RepositoryBuilder, MigrationBuilder
│   ├── Schema/                   — EntitySchema, SchemaValidator
│   └── Writer/FileWriter.php
│
├── Http/
│   ├── Request.php               — value object: headers, query, body, method, path
│   ├── Response.php              — value object + legacy json() echo method
│   ├── Router.php                — GET/POST/PUT/DELETE route dispatch
│   ├── Pipeline.php              — immutable middleware chain runner
│   └── Middleware/
│       └── MiddlewareInterface.php — handle(Request, callable): Response
│
├── Repository/
│   └── BaseRepository.php        — auto-scoped CRUD + transactions
│
└── Tenant/
    ├── TenantContext.php          — static singleton holding current tenant ID
    ├── TenantGuard.php            — throws if TenantContext is empty
    ├── TenantConnectionResolver.php — resolves + caches PDO connection per tenant
    ├── TenantResolverFactory.php  — creates header or subdomain resolver
    ├── TenantResolverInterface.php
    ├── TenantProvisioner.php      — creates/drops tenant DB, runs migrations
    ├── TenantRepository.php       — CRUD on the main DB tenants table
    ├── TenantService.php          — onboard/suspend/resume/offboard
    ├── RequestLifecycle.php       — clears context + connection cache per request
    └── Resolver/
        ├── HeaderTenantResolver.php    — reads tenant from a request header
        └── SubdomainTenantResolver.php — extracts tenant from subdomain
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
  │     CREATE TABLE IF NOT EXISTS tenants (always, both strategies)
  │
  ├── Container::registerBindings()
  │     singletons: TenantRepository, TenantProvisioner, TenantService
  │
  └── if $resolveTenant && tenancy.enabled:
        TenantResolverFactory::create() → resolver.resolve($context)
        TenantContext::setTenantId()   ← throws LogicException if already set
        if strategy === database:
          TenantRepository::findByTenantId() — throws if not found or suspended
```

Pass `false` as the second argument to skip tenant resolution (CLI commands, provisioning).

---

## Multi-Tenancy Strategies

The pivot is `tenancy.strategy` in `config/application.yaml`.

### `shared` — single database, tenant_id column

Every table has a `tenant_id` column. `BaseRepository` automatically appends `WHERE tenant_id = :tenant_id` (and `AND tenant_id = :tenant_id` on writes) when `shouldApplyTenantScope()` returns true.

```
entity_forge
  ├── tenants        ← registry
  └── users          ← tenant_id = 'acme' | 'corp' | ...
```

### `database` — one database per tenant

Each tenant gets its own database named `{base_db}_{tenantId}`. Connection resolution selects the correct database. No `tenant_id` column needed; isolation is at the connection level.

```
entity_forge            ← main DB: tenants registry only
entity_forge_acme       ← tenant DB: all application data
entity_forge_corp       ← tenant DB: all application data
```

`TenantConnectionResolver` caches open connections in a static registry keyed by database name. Call `TenantConnectionResolver::flush()` (or `RequestLifecycle::begin()`) between requests in worker-mode PHP.

---

## Tenant Lifecycle

### Onboarding

```
TenantService::onboard($tenantId, $name)
  ├── validate $tenantId matches ^[a-zA-Z0-9_-]+$ — throws if invalid
  ├── TenantRepository::exists() — throws if already registered
  ├── TenantProvisioner::create()
  │     ├── CREATE DATABASE IF NOT EXISTS {base}_{tenantId}
  │     ├── MigrationRunner::run('database/migrations')
  │     └── on failure: DROP DATABASE {base}_{tenantId}, re-throw
  └── TenantRepository::create() — INSERT into tenants
```

`bin/ef tenant:create <tenantId> [--name=<display name>]` calls this flow.

### Suspension / Resumption

```php
$service->suspend($tenantId);   // sets status = 'suspended'
$service->resume($tenantId);    // sets status = 'active'
```

Suspended tenants are blocked at `Application::boot()` — the status check in `assertTenantActive()` throws before any repository is instantiated.

### Offboarding

```php
$service->offboard($tenantId);
// database strategy: TenantProvisioner::drop() → DROP DATABASE IF EXISTS
// both strategies:   TenantRepository::deleteByTenantId()
```

---

## HTTP Layer

### Request

Immutable value object. Constructed directly (tests, workers) or captured from globals:

```php
$request = new Request(headers: [...], query: [...], body: [...], method: 'POST', path: '/users');
$request = Request::capture();   // reads $_SERVER, $_GET, $_POST, getallheaders()
```

Route parameters extracted by the router are available via:

```php
$request->param('id');    // single named parameter
$request->params();       // all parameters as array
```

### Response

Three output modes: immutable builder, streaming, and legacy direct-echo.

```php
// Immutable builder — pipeline / router path
$response = (new Response())
    ->withJson(['id' => 1], 201)
    ->withHeader('X-Request-Id', $id);
$response->send();   // http_response_code + headers + echo body

// Streaming — caller echoes chunks, controls flush timing
(new Response())
    ->withStatus(200)
    ->withHeader('Content-Type', 'text/csv')
    ->stream(function (): void {
        echo "id,name\n";
        flush();
    });

// Legacy direct-output (kept for backwards compatibility)
(new Response())->json(['ok' => true], 200);
```

### Pipeline

Immutable middleware chain. Each `pipe()` call returns a new instance.

```php
$pipeline = (new Pipeline())
    ->pipe(new AuthMiddleware())
    ->pipe(new TenantMiddleware());

$response = $pipeline->run($request, fn(Request $req): Response => $router->dispatch($req));
```

Middleware is executed outermost-first. `$next` is a `callable(Request): Response`.

### Router

Backed by `nikic/fast-route`. Supports exact paths and `{name}` parameter segments.

```php
$router = new Router();
$router->get('/users',        fn(Request $req): Response => ...);
$router->post('/users',       fn(Request $req): Response => ...);
$router->get('/users/{id}',   fn(Request $req): Response => ...);  // $req->param('id')
$router->delete('/users/{id}', fn(Request $req): Response => ...);

$response = $router->dispatch($request);  // 404 not found, 405 method not allowed
```

Routes are matched in registration order — register exact paths before parameterised ones.

---

## Repository Layer

`BaseRepository` handles connection resolution, tenant scoping, and standard CRUD. Generated repositories extend it and contain no logic by default.

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

`resolveTableName()` derives the table name from the class name (`UserRepository` → `users`). Override `$this->table` in the subclass constructor to use a custom name.

Column names passed to `create()`, `where()`, and `update()` are validated against `^[a-zA-Z0-9_]+$` before SQL interpolation. `InvalidArgumentException` is thrown on violation.

---

## Migration System

`MigrationRunner` tracks executed migrations in a `migrations` table (auto-created). Rollback undoes all migrations from the most recent batch.

```
database/migrations/
  20260101_000001_create_users_table.up.sql
  20260101_000001_create_users_table.down.sql
```

Every `.up.sql` must have a paired `.down.sql`. A missing down file aborts rollback with an exception.

### Keeping Tenant Databases in Sync

When a new migration is added, existing tenant databases do not automatically receive it. Run:

```bash
php bin/ef migrate:all-tenants
php bin/ef migrate:all-tenants --parallel 5   # run 5 concurrent workers
php bin/ef migrate:all-tenants --dry-run       # preview without applying
```

Only active tenants (`status = 'active'`) are included. Suspended tenants are skipped. Workers are spawned via `symfony/process` — cross-platform on Linux, macOS, and Windows. Failures per-tenant are reported but do not stop other tenants from being migrated.

---

## Code Generation

Entity JSON schemas live in `config/entities/*.json`. A schema drives three builders:

```json
{
  "entity": "Order",
  "fields": { "id": "int", "amount": "float", "status": "string" },
  "relations": { "belongsTo": { "User": "user_id" } },
  "indexes": [
    { "columns": ["status"] },
    { "columns": ["user_id", "status"], "unique": true }
  ]
}
```

`relations.belongsTo` emits `CONSTRAINT fk_… FOREIGN KEY` clauses. `indexes` emits `INDEX` or `UNIQUE INDEX` clauses. Both sections are optional.

Output:
- `app/Entity/Order.php`
- `app/Repository/OrderRepository.php`
- `database/migrations/{timestamp}_create_orders_table.up.sql` + `.down.sql`

`generate:all` uses a single `EntityGenerator` instance to guarantee monotonically ordered migration timestamps within a session.

---

## CLI Commands

| Command                  | Key options                        | Description                                          |
|--------------------------|------------------------------------|------------------------------------------------------|
| `generate <Entity>`      |                                    | Generate entity + repository from JSON schema        |
| `generate:all`           | `--config-dir`                     | Generate all schemas in `config/entities/`           |
| `migrate`                | `--dry-run`                        | Run pending migrations on the main database          |
| `migrate:rollback`       | `--dry-run`                        | Roll back the last migration batch                   |
| `migrate:all-tenants`    | `--dry-run`, `--parallel N`        | Run pending migrations on every active tenant DB     |
| `tenant:create <id>`     | `--name`                           | Onboard a new tenant                                 |

---

## Concurrency (Worker-Mode PHP)

`TenantContext` is a static singleton. In PHP-FPM each process handles one request so static state is reset automatically. In long-lived workers (Swoole, RoadRunner, Laravel Octane), static state persists between requests.

`TenantContext::setTenantId()` throws `LogicException` if a tenant is already set. This turns a forgotten `RequestLifecycle::begin()` call into an immediate hard error rather than a silent wrong-tenant data leak.

Wrap each request loop iteration:

```php
RequestLifecycle::begin();   // clears TenantContext + connection cache

// ... handle request ...

RequestLifecycle::end();     // clears again on teardown
```

---

## Key Invariants

1. **Tenant isolation is never optional.** Every query decision must account for both strategies.
2. **Main DB ↔ tenant DB boundary is sacred.** The `tenants` registry lives only in the main DB. Application data lives only in tenant DBs.
3. **Repository instances are not reusable across tenant switches.** Instantiate fresh after `TenantContext::setTenantId()`.
4. **Idempotent infrastructure.** `CREATE TABLE IF NOT EXISTS`, batch-tracked migrations, `CoreSchemaManager` — follow the pattern.
5. **Explicit over implicit.** Tenant resolution, connection selection, scope injection are always conscious calls.
6. **Configuration drives generation.** New entity types go through the generator pipeline, not handwritten files.

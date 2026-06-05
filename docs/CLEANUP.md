# Known Limitations & Future Work

This document tracks deliberate gaps and deferred work in the current codebase.

---

## Tenant Resolver

**SubdomainTenantResolver minimum parts are configurable.** The default requires 3-part hosts (`acme.example.com`). Set `tenancy.subdomain_min_parts: 2` in `application.yaml` to support two-part hosts like `acme.io`. Single-part hosts (e.g. `localhost`) always throw regardless of this setting.

**No JWT or session resolver.** `TenantResolverInterface` is designed for extension. Wire a new implementation into `TenantResolverFactory` and configure `tenancy.resolver` accordingly.

---

## HTTP Layer

**Parameterised routes are supported.** `Router` compiles `{name}` segments into named regex captures at registration time. Extracted values are available via `$request->param('name')` and `$request->params()`. Routes registered earlier take precedence — register exact paths before wildcard patterns to ensure correct priority.

**Response streaming is supported.** `Response::stream(callable $body)` sends status and headers then delegates output to the callable — the caller echoes chunks and controls flush timing. Use `withStatus()` and `withHeader()` to set the status and headers before calling `stream()`.

```php
(new Response())
    ->withStatus(200)
    ->withHeader('Content-Type', 'text/csv')
    ->stream(function (): void {
        echo "id,name\n";
        flush();
        foreach ($rows as $row) {
            echo "{$row['id']},{$row['name']}\n";
            flush();
        }
    });
```

---

## Migration System

**`migrate:all-tenants` supports parallel workers.** Pass `--parallel N` (default 5) to run up to N tenant migrations concurrently. Uses `symfony/process` to spawn subprocesses — cross-platform (Linux, macOS, Windows). Each subprocess calls `migrate:all-tenants --tenant=<id>` and runs in isolation. `--dry-run` is forwarded to subprocesses automatically.

**`--dry-run` is supported.** `migrate`, `migrate:rollback`, and `migrate:all-tenants` all accept `--dry-run`. The runner skips all writes and prefixes output with `[DRY RUN]`. Executed-migration state is read best-effort (falls back to treating all as pending if the migrations table does not yet exist).

**`migrate:all-tenants` skips suspended tenants.** Uses `TenantRepository::allActive()` which filters by `status = 'active'`. Tenants in any other status are excluded from bulk migrations.

---

## Connection Pooling

**`TenantConnectionResolver` uses a static in-memory pool.** This is fine for PHP-FPM (process-per-request) and for single-request workers. It does not persist connections across process restarts. In persistent worker scenarios (Swoole, RoadRunner), the pool is per-worker-process, which is the expected behaviour.

---

## Concurrency

**`TenantContext` is a static singleton with lifecycle enforcement.** `setTenantId()` throws `LogicException` if a tenant is already set, making omitted `RequestLifecycle::begin()` calls a hard error rather than a silent data leak. `RequestLifecycle::begin()` calls `clear()` first, so correctly structured worker loops are unaffected.

---

## Code Generator

**`generate:all` defaults to CWD-relative `config/entities`.** If invoked from outside the project root without `--config-dir`, it will exit with an error (`Directory not found: config/entities`). Always run from the project root or pass `--config-dir` explicitly.

**Relations and indexes are supported in the generator.** `MigrationBuilder` emits `FOREIGN KEY` constraints from `relations.belongsTo` and `INDEX` / `UNIQUE INDEX` clauses from `indexes`. Both sections are optional and validated by `SchemaValidator`.

```json
"relations": { "belongsTo": { "User": "user_id" } },
"indexes": [
  { "columns": ["email"], "unique": true },
  { "columns": ["status"] }
]
```

---

## Security

**Column names are validated in `BaseRepository`.** `create()`, `where()`, and `update()` run each column name through `assertColumnName()` which enforces `^[a-zA-Z0-9_]+$` and throws `InvalidArgumentException` on violation. The table name is derived from the class name and is not validated — do not allow external input to influence which repository class is instantiated.

**Tenant DB names are derived from tenant IDs.** `{base}_{tenantId}` is used directly in a `CREATE DATABASE` statement via `PDO::exec`. `TenantService::onboard()` validates that the tenant ID matches `^[a-zA-Z0-9_-]+$` and throws if it does not. Direct calls to `TenantProvisioner::create()` bypass this check — do not call it with user-supplied input.

---

## Dependency Injection

**`Container` is available.** `src/Core/Container.php` supports `bind()`, `singleton()`, `instance()`, and `make()` with reflection-based auto-wiring. `Application` creates a container on construction, registers `TenantRepository`, `TenantProvisioner`, and `TenantService` as singletons, and exposes it via `getContainer()`.

`TenantService` accepts optional constructor injection for `TenantRepository` and `TenantProvisioner` — pass them directly in tests instead of using `overload:` mocks. Extend this pattern to other classes as needed.

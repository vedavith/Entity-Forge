# Known Limitations & Future Work

This document tracks deliberate gaps and deferred work in the current codebase.

---

## Tenant Resolver

**SubdomainTenantResolver requires 3-part hosts.** `example.com` (2 parts) throws. Two-level domains (`acme.io`) are not supported as tenant hosts without subdomains. This is by design — the resolver always strips the leading segment.

**No JWT or session resolver.** `TenantResolverInterface` is designed for extension. Wire a new implementation into `TenantResolverFactory` and configure `tenancy.resolver` accordingly.

---

## HTTP Layer

**No regex or parameterised routes.** `Router` does exact path matching. Dynamic segments like `/users/{id}` are not supported. This is intentional scope — add a pattern-matching router (e.g. FastRoute) when needed.

**No response streaming.** `Response::send()` buffers the full body in memory before output. Acceptable for API responses; replace with a streaming approach for file downloads.

---

## Migration System

**`migrate:all-tenants` has no concurrency control.** Migrations run against tenant databases sequentially. On large deployments (hundreds of tenants), consider parallelising with a process pool or a queue.

**No dry-run mode.** Neither `MigrationRunner` nor the CLI commands support `--dry-run`. Add it as a future option.

**`migrate:all-tenants` only targets active tenants.** Suspended tenants are included in `TenantRepository::all()` and will be migrated regardless of status. Filter by `status = 'active'` if you want to exclude suspended tenants from bulk migrations.

---

## Connection Pooling

**`TenantConnectionResolver` uses a static in-memory pool.** This is fine for PHP-FPM (process-per-request) and for single-request workers. It does not persist connections across process restarts. In persistent worker scenarios (Swoole, RoadRunner), the pool is per-worker-process, which is the expected behaviour.

---

## Concurrency

**`TenantContext` is a static singleton.** Safe for PHP-FPM. For worker-mode PHP, `RequestLifecycle::begin()` / `end()` must be called around each request. There is no enforcement mechanism — the application will silently serve the wrong tenant if lifecycle hooks are omitted.

---

## Code Generator

**`generate:all` defaults to CWD-relative `config/entities`.** If invoked from outside the project root without `--config-dir`, it will fail silently (no files found). Always run from the project root or pass `--config-dir` explicitly.

**No relation or index support in the generator.** `MigrationBuilder` only handles column definitions. Foreign keys, composite indexes, and unique constraints must be added to the generated SQL files manually.

---

## Security

**No input sanitisation in `BaseRepository`.** Table and column names in `where()`, `update()`, and `delete()` are interpolated directly into SQL strings. These values come from internal code (not user input), so injection is not a current risk — but never pass user-supplied strings as column names.

**Tenant DB names are derived from tenant IDs.** `{base}_{tenantId}` is used directly in a `CREATE DATABASE` statement via `PDO::exec`. Tenant IDs should be validated to be alphanumeric before onboarding. `TenantService::onboard()` does not enforce this — add a validation step if tenant IDs are user-supplied.

---

## Dependency Injection

**No DI container.** Components instantiate their dependencies with `new` internally. This makes unit testing require `Mockery::mock('overload:...')` with `#[RunInSeparateProcess]`. A DI container would allow constructor injection and standard mocking patterns.

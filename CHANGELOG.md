# Changelog

All notable changes to EntityForge are documented here.

---

## [v2.2.0] — 2026-07-01

### Added
- **Dynamic Schema Extension** — opt-in `"metadata": true` flag in entity JSON schemas emits a `metadata JSON NULL` column in the migration and exposes `getMeta`, `setMeta`, and `getAllMeta` on `BaseRepository`. Allows tenants to store arbitrary custom field values without schema changes.
- **`tenant_fields` registry table** — auto-created by `CoreSchemaManager` on boot. Stores per-tenant custom field definitions (name, type, label, required). Works with both `shared` and `database` tenancy strategies.
- **`TenantFieldRegistry`** — new class for registering, listing, and removing custom field definitions per tenant. Includes `validate()` for checking submitted values against registered definitions.
- **`field:add` CLI command** — `php bin/ef field:add <entity> <field_name> <type> --tenant=<id> [--label] [--required]`
- **`field:list` CLI command** — `php bin/ef field:list <entity> --tenant=<id>`
- **`field:remove` CLI command** — `php bin/ef field:remove <id> --tenant=<id>`
- Added `keywords`, `homepage`, and `authors` to `composer.json` for Packagist discoverability.

---

## [v2.1.1] — 2026-06-27

### Fixed
- Minor stability and test coverage improvements.

---

## [v2.1.0] — 2026-06-27

### Added
- `migrate:all-tenants` command — runs pending migrations across every active tenant DB in parallel (configurable via `--parallel N`, default 5). Suspended tenants are skipped. Per-tenant failures are reported without halting other tenants.
- `TenantService::suspend()`, `resume()`, and `offboard()` — full tenant lifecycle management. `offboard()` drops the tenant DB (database strategy) and removes the registry entry atomically.
- Suspended tenants are blocked at `Application::boot()` — `assertTenantActive()` throws before any repository is instantiated.
- `TenantProvisioner` rolls back atomically on migration failure — if migrations fail after the DB is created, the DB is dropped before re-throwing. No orphaned databases.
- `RequestLifecycle::begin()` / `end()` — safe wrappers for persistent runtimes (Swoole, RoadRunner, Octane) that clear `TenantContext` and flush the connection cache between requests.
- Four tenant resolvers: `header`, `subdomain`, `jwt`, `session`. Configurable via `tenancy.resolver` in `application.yaml`.
- `make:middleware` and `make:controller` scaffolding commands.
- Streaming response support via `Response::stream(callable)`.
- `Request::withAttribute()` / `getAttribute()` — middleware-to-handler attribute passing.

---

## [v2.0.0] — 2026-06-14

### Added
- Initial public release.
- Configuration-driven code generation from JSON entity schemas: `EntityBuilder`, `RepositoryBuilder`, `MigrationBuilder`.
- Two tenancy strategies: `shared` (tenant_id column scoping) and `database` (one DB per tenant).
- `BaseRepository` with `create`, `findAll`, `findById`, `where`, `update`, `delete`, and transaction methods. Column names validated against `^[a-zA-Z0-9_]+$` before SQL interpolation.
- `CoreSchemaManager` — idempotent `tenants` table creation on every boot.
- `MigrationRunner` — batch-tracked forward and rollback migrations with dry-run mode.
- HTTP layer: `Router` (FastRoute), immutable `Pipeline`, immutable `Request` / `Response`.
- DI container with `bind`, `singleton`, `instance`, and reflection-based autowire.
- `TenantContext` static singleton with `LogicException` guard against double-set.
- `generate`, `generate:all`, `migrate`, `migrate:rollback`, `tenant:create` CLI commands.

[v2.2.0]: https://github.com/vedavith/Entity-Forge/compare/v2.1.1...v2.2.0
[v2.1.1]: https://github.com/vedavith/Entity-Forge/compare/v2.1.0...v2.1.1
[v2.1.0]: https://github.com/vedavith/Entity-Forge/compare/v2.0.0...v2.1.0
[v2.0.0]: https://github.com/vedavith/Entity-Forge/releases/tag/v2.0.0

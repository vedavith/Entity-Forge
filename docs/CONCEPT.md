# Core Concepts

## Application
The boot entry point. Loads and merges YAML config, validates required keys, runs `CoreSchemaManager`, and optionally resolves the current tenant. Pass `false` as the second argument to `boot()` to skip tenant resolution (used by CLI commands).

## Entity
A configuration-defined model described by a JSON schema in `config/entities/`. Drives generation of a PHP class, a repository, and SQL migration files.

## Repository
A data-access layer that auto-scopes queries to the current tenant. Generated repositories extend `BaseRepository` and inherit CRUD methods, transaction support, and tenant scoping. Never reuse a repository instance across tenant switches.

## BaseRepository
The abstract parent for all repositories. Resolves its PDO connection via `TenantConnectionResolver`, injects `WHERE tenant_id = :tenant_id` for shared strategy, and exposes `create`, `findAll`, `findById`, `where`, `update`, `delete`, `beginTransaction`, `commit`, `rollback`.

## Tenant
A logical boundary that isolates data between customers. Represented by a `tenant_id` string. Stored in the `tenants` table on the main database.

## Tenant Strategy

**`shared`** — all tenants share one database. Every table has a `tenant_id` column; `BaseRepository` appends it to every query automatically.

**`database`** — each tenant has its own database named `{base_db}_{tenantId}`. Isolation is at the connection level; no `tenant_id` column is needed.

## TenantContext
A static singleton that holds the current tenant ID for the lifetime of a request. Use `TenantContext::setTenantId()`, `getTenantId()`, `hasTenantId()`, and `clear()`. In worker-mode PHP (Swoole, RoadRunner), call `RequestLifecycle::begin()` at the start of each request to reset it.

## TenantConnectionResolver
Resolves and caches the PDO connection for the current tenant. In `shared` mode it returns a connection to the main DB. In `database` mode it connects to `{base_db}_{tenantId}`. Connections are pooled in a static registry; call `flush()` to clear the cache.

## TenantResolver
Extracts a tenant ID from request context. Two implementations ship:
- `HeaderTenantResolver` — reads a configurable header (default: `X-Tenant-ID`)
- `SubdomainTenantResolver` — extracts the leading subdomain from the host (e.g. `acme.example.com` → `acme`)

Configured via `tenancy.resolver` in `application.yaml`. Add new resolvers by implementing `TenantResolverInterface` and registering them in `TenantResolverFactory`.

## TenantService
The intended entry point for tenant lifecycle operations:

| Method | What it does |
|---|---|
| `onboard($id, $name)` | Provisions DB + runs migrations + registers in tenants table |
| `suspend($id)` | Sets `status = 'suspended'`; blocks future boots |
| `resume($id)` | Sets `status = 'active'` |
| `offboard($id)` | Drops tenant DB (database strategy) + removes tenant record |

## TenantProvisioner
Low-level operator for the tenant database. `create()` runs `CREATE DATABASE` then executes all migrations. On migration failure it drops the partially-created database and re-throws — no orphaned databases. `drop()` runs `DROP DATABASE IF EXISTS`.

## CoreSchemaManager
Ensures the `tenants` table exists on the main database. Runs on every `Application::boot()` for both strategies. Idempotent (`CREATE TABLE IF NOT EXISTS`).

## RequestLifecycle
A helper for long-lived PHP worker processes. `begin()` and `end()` both clear `TenantContext` and flush the connection cache in `TenantConnectionResolver`, preventing tenant state from leaking between requests.

## MigrationRunner
Executes `.up.sql` files in filename order, records each in a `migrations` table with a batch number, and skips already-executed ones. Rollback reverses all migrations from the last batch using paired `.down.sql` files.

## Pipeline
An immutable middleware chain. Each `pipe()` returns a new `Pipeline` instance. `run(Request, callable): Response` processes the chain outermost-first, then calls the destination handler. Middleware implements `MiddlewareInterface::handle(Request, callable): Response`.

## Router
A simple path-based request dispatcher. Register handlers with `get()`, `post()`, `put()`, `delete()`. `dispatch(Request): Response` returns a `404 Not Found` response for unregistered routes.

## Request
An immutable value object representing an HTTP request. Constructed directly or captured from PHP superglobals via `Request::capture()`. Provides `header()`, `query()`, `body()`, `method()`, and `path()`.

## Response
A dual-mode HTTP response. The immutable builder path (`withJson`, `withStatus`, `withHeader`, `send`) is used by the Pipeline and Router. The legacy `json()` method echoes directly and is kept for backwards compatibility.

## ConfigLoader
Loads one or more YAML files and merges them with `array_replace_recursive`. Files are merged in order; later files override earlier ones. `saas.yaml` is loaded first, `application.yaml` second.

## ConfigValidator
Validates required config keys before boot. Throws an `Exception` with a descriptive message if `tenancy.enabled` or any `database.*` key (`driver`, `host`, `port`, `database`, `username`, `password`) is missing.

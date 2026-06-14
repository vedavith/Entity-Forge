# Core Concepts

## Application
The boot entry point. Loads and merges YAML config, validates required keys, runs `CoreSchemaManager`, registers core bindings in the `Container`, and optionally resolves the current tenant. Pass `false` as the second argument to `boot()` to skip tenant resolution (used by CLI commands). Access the container via `getContainer()`.

## Entity
A configuration-defined model described by a JSON schema in `config/entities/`. Drives generation of a PHP class, a repository, and SQL migration files. Relation entries in the schema become typed properties on the generated class: `belongsTo` entries produce a nullable typed property (`public ?User $user = null;`), `hasMany` entries produce a typed array property (`/** @var Order[] */ public array $orders = [];`). Both include the appropriate `use` import.

## Repository
A data-access layer that auto-scopes queries to the current tenant. Generated repositories extend `BaseRepository` and inherit CRUD methods, transaction support, and tenant scoping. Never reuse a repository instance across tenant switches.

## BaseRepository
The abstract parent for all repositories. Resolves its PDO connection via `TenantConnectionResolver`, injects `WHERE tenant_id = :tenant_id` for shared strategy, and exposes `create`, `findAll`, `findById`, `where`, `update`, `delete`, `beginTransaction`, `commit`, `rollback`. Column names passed to `create()`, `where()`, and `update()` are validated against `^[a-zA-Z0-9_]+$` — `InvalidArgumentException` is thrown if a column name fails.

## Tenant
A logical boundary that isolates data between customers. Represented by a `tenant_id` string. Stored in the `tenants` table on the main database.

## Tenant Strategy

**`shared`** — all tenants share one database. Every table has a `tenant_id` column; `BaseRepository` appends it to every query automatically.

**`database`** — each tenant has its own database named `{base_db}_{tenantId}`. Isolation is at the connection level; no `tenant_id` column is needed.

## TenantContext
A static singleton that holds the current tenant ID for the lifetime of a request. Use `TenantContext::setTenantId()`, `getTenantId()`, `hasTenantId()`, and `clear()`. `setTenantId()` throws `LogicException` if a tenant ID is already set — call `RequestLifecycle::begin()` (which calls `clear()` first) at the start of each request in worker-mode PHP to avoid this.

## TenantConnectionResolver
Resolves and caches the PDO connection for the current tenant. In `shared` mode it returns a connection to the main DB. In `database` mode it connects to `{base_db}_{tenantId}`. Connections are pooled in a static registry; call `flush()` to clear the cache.

## TenantResolver
Extracts a tenant ID from request context. Four implementations ship:
- `HeaderTenantResolver` — reads a configurable header (default: `X-Tenant-ID`). Configure via `tenancy.header_key`.
- `SubdomainTenantResolver` — extracts the leading subdomain from the host (e.g. `acme.example.com` → `acme`). Set `tenancy.subdomain_min_parts: 2` to support two-part hosts like `acme.io`.
- `JwtTenantResolver` — decodes and verifies a Bearer JWT from the `Authorization` header, then extracts a configurable claim (default: `tenant_id`). Configure via `tenancy.jwt_public_key`, `tenancy.jwt_algorithm` (default `RS256`), and `tenancy.jwt_tenant_claim`.
- `SessionTenantResolver` — reads tenant from `$context['session']` when provided (useful in tests), otherwise falls back to PHP `$_SESSION`. Configure the key via `tenancy.session_key` (default: `tenant_id`).

Configured via `tenancy.resolver: header | subdomain | jwt | session` in `application.yaml`. Add new resolvers by implementing `TenantResolverInterface` and registering them in `TenantResolverFactory`.

## TenantService
The intended entry point for tenant lifecycle operations:

| Method | What it does |
|---|---|
| `onboard($id, $name)` | Validates ID format, provisions DB + runs migrations + registers in tenants table |
| `suspend($id)` | Sets `status = 'suspended'`; blocks future boots |
| `resume($id)` | Sets `status = 'active'` |
| `offboard($id)` | Drops tenant DB (database strategy) + removes tenant record |

`onboard()` rejects tenant IDs that do not match `^[a-zA-Z0-9_-]+$`. `TenantRepository` and `TenantProvisioner` can be injected via constructor for testing without `overload:` mocks.

## TenantProvisioner
Low-level operator for the tenant database. `create()` runs `CREATE DATABASE` then executes all migrations. On migration failure it drops the partially-created database and re-throws — no orphaned databases. `drop()` runs `DROP DATABASE IF EXISTS`.

## CoreSchemaManager
Ensures the `tenants` table exists on the main database. Runs on every `Application::boot()` for both strategies. Idempotent (`CREATE TABLE IF NOT EXISTS`).

## RequestLifecycle
A helper for long-lived PHP worker processes. `begin()` and `end()` both clear `TenantContext` and flush the connection cache in `TenantConnectionResolver`, preventing tenant state from leaking between requests.

## MigrationRunner
Executes `.up.sql` files in filename order, records each in a `migrations` table with a batch number, and skips already-executed ones. Rollback reverses all migrations from the last batch using paired `.down.sql` files. Both `run()` and `rollback()` accept a `$dryRun` flag — in dry-run mode all writes are skipped and output is prefixed with `[DRY RUN]`.

## Pipeline
An immutable middleware chain. Each `pipe()` returns a new `Pipeline` instance. `run(Request, callable): Response` processes the chain outermost-first, then calls the destination handler. Middleware implements `MiddlewareInterface::handle(Request, callable): Response`.

## AuthMiddlewareInterface
A marker interface extending `MiddlewareInterface` that designates a middleware as the auth integration point. Implementations authenticate the request via any external provider and attach the resolved identity with `$request->withAttribute('user', $identity)` before calling `$next`. EntityForge does not implement auth; it defines where auth hooks in.

## Router
A FastRoute-backed request dispatcher. Register handlers with `get()`, `post()`, `put()`, `delete()`. Paths support `{name}` parameter segments — extracted values are available via `$request->param('name')` and `$request->params()`. `dispatch(Request): Response` returns `404 Not Found` for unregistered routes and `405 Method Not Allowed` for method mismatches. Routes match in registration order.

## Request
An immutable value object representing an HTTP request. Constructed directly or captured from PHP superglobals via `Request::capture()`. Provides `header()`, `query()`, `body()`, `method()`, `path()`, `param()`, and `params()`. Route parameters are attached by the Router via `withParams()` before the handler is called. Arbitrary per-request data (resolved user, trace IDs) is stored as attributes via `withAttribute(key, value)` (returns a new instance) and read with `getAttribute(key, default)`.

## Response
A tri-mode HTTP response. The immutable builder path (`withJson`, `withStatus`, `withHeader`, `send`) is used by the Pipeline and Router. `stream(callable)` sends headers then delegates output to the caller for chunk-by-chunk streaming without buffering. The legacy `json()` method echoes directly and is kept for backwards compatibility.

## Container
A lightweight DI container. Supports `bind()` (new instance per call), `singleton()` (shared instance), `instance()` (pre-built object), and `make()`. `make()` resolves typed constructor parameters recursively via reflection (auto-wiring). `Application` creates one on construction, registers core singletons, and exposes it via `getContainer()`.

## ConfigLoader
Loads one or more YAML files and merges them with `array_replace_recursive`. Files are merged in order; later files override earlier ones. `saas.yaml` is loaded first, `application.yaml` second.

## ConfigValidator
Validates required config keys before boot. Throws an `Exception` with a descriptive message if `tenancy.enabled` or any `database.*` key (`driver`, `host`, `port`, `database`, `username`, `password`) is missing.

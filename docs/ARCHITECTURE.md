# 🏗️ EntityForge Architecture

## 🎯 Overview

EntityForge is a **configuration-driven, multi-tenant SaaS framework** built in PHP.

It supports:

* Code generation via JSON configs
* Multi-tenant strategies (shared & database)
* Automated migrations and rollback
* Tenant provisioning and lifecycle management

---

# 🧠 Core Principles

* **Separation of concerns**
* **Explicit over implicit**
* **Configuration-driven architecture**
* **Tenant isolation by design**
* **Idempotent infrastructure**

---

# 📦 System Architecture

```
Application
│
├── Core Layer
│   ├── Application
│   ├── ConfigLoader
│   └── CoreSchemaManager
│
├── Tenant Layer
│   ├── TenantContext
│   ├── TenantResolver
│   ├── TenantConnectionResolver
│   ├── TenantProvisioner
│   ├── TenantRepository
│   └── TenantService
│
├── Database Layer
│   ├── Connection
│   ├── MigrationRunner
│
├── Generator Layer
│   ├── EntityGenerator
│   ├── Builders (Entity, Repository, Migration)
│   └── FileWriter
│
├── Repository Layer
│   ├── BaseRepository
│   └── Generated Repositories
│
└── Console Layer
    ├── GenerateCommand
    ├── GenerateAllCommand
    ├── MigrateCommand
    ├── RollbackCommand
    └── TenantCreateCommand
```

---

# 🧩 Core Components

## 1. Application

Handles:

* Bootstrapping config
* Tenant resolution (optional)
* Core schema initialization

```php
$app->boot($context, $resolveTenant);
```

---

## 2. CoreSchemaManager

Responsible for:

* Ensuring framework-level tables exist

### Currently manages:

* `tenants` table

✔ Runs automatically during application boot
✔ Idempotent (`CREATE TABLE IF NOT EXISTS`)

---

## 3. Tenant System

### TenantContext

* Stores current tenant ID (runtime state)

---

### TenantResolver

* Extracts tenant from request context

---

### TenantConnectionResolver

* Resolves DB connection based on strategy:

| Strategy | Behavior      |
| -------- | ------------- |
| shared   | single DB     |
| database | DB per tenant |

---

### TenantProvisioner

Handles:

* Creating tenant database
* Running migrations

---

### TenantRepository

Uses **main DB** to:

* Store tenant records
* Check existence
* List tenants

---

### TenantService

Entry point for onboarding:

```php
$service->onboard($tenantId, $name);
```

Flow:

```
Check → Create DB → Run migrations → Register tenant
```

---

# 🗄️ Database Architecture

## 🔹 Main Database

```
entity_forge
  └── tenants
```

Stores:

* tenant metadata
* lifecycle state

---

## 🔹 Tenant Databases

```
entity_forge_tenant_1
entity_forge_tenant_2
```

Stores:

* application data (users, orders, etc.)

---

# 🔄 Multi-Tenant Strategies

## 1. Shared Database

* Single DB
* `tenant_id` column used for isolation

```text
users
  id
  name
  tenant_id
```

---

## 2. Database per Tenant

* One DB per tenant
* No `tenant_id` needed

```text
tenant_1_db → users
tenant_2_db → users
```

---

# 🧱 Repository Layer

## BaseRepository

Handles:

* DB connection resolution
* Tenant scoping
* Insert/query abstraction

### Features:

* `create()`
* `findAll()`
* `findById()`
* `where()`

---

## Generated Repositories

* Extend BaseRepository
* Contain no logic by default
* Used for customization when needed

---

# ⚙️ Migration System

## MigrationRunner

Supports:

* Running migrations
* Tracking execution
* Rollback by batch

---

## Migration Structure

```
database/migrations/
  2026_..._create_users_table.up.sql
  2026_..._create_users_table.down.sql
```

---

## Features

* Idempotent execution
* Batch tracking
* Rollback support

---

# 🏗️ Generator System

## Input

```json
{
  "entity": "User",
  "multiTenant": true,
  "timestamps": true,
  "fields": {
    "name": "string",
    "email": "string"
  }
}
```

---

## Output

* Entity class
* Repository class
* Migration files

---

# 🧰 CLI Commands

| Command          | Purpose                |
| ---------------- | ---------------------- |
| generate         | Generate single entity |
| generate:all     | Generate all entities  |
| migrate          | Run migrations         |
| migrate:rollback | Rollback last batch    |
| tenant:create    | Provision new tenant   |

---

# 🔁 Tenant Lifecycle

## Onboarding Flow

```
TenantService
   ↓
TenantProvisioner
   ↓
Create DB
   ↓
Run Migrations
   ↓
Register Tenant
```

---

## Runtime Flow

```
Request
  ↓
Application boot
  ↓
Tenant resolved
  ↓
Connection resolved
  ↓
Repository used
```

---

# ⚠️ Key Rules

## 🔴 Never mix:

| Concern         | Location          |
| --------------- | ----------------- |
| tenant registry | main DB           |
| user data       | tenant DB         |
| core schema     | CoreSchemaManager |
| business schema | migrations        |

---

## 🔴 Never reuse:

* Repository instances across tenants
* Connections across tenant switches

---

## 🔴 Always:

* Boot before using repositories
* Create new repository after tenant switch

---

# 🚀 Current Status

## Completed

* ✅ Multi-tenant architecture
* ✅ DB-per-tenant strategy
* ✅ Migration system with rollback
* ✅ Code generator
* ✅ Tenant provisioning
* ✅ Tenant registry
* ✅ Query layer

---

## Upcoming

* 🔲 Middleware (auto tenant resolution)
* 🔲 Dependency injection container
* 🔲 API layer

---

# 🧠 Final Thought

This is no longer just a framework.

It is:

> **A foundation for building real multi-tenant SaaS systems**

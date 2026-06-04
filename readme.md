# 🚀 EntityForge

**EntityForge** is a WIP open source configuration-driven, multi-tenant SaaS framework built in PHP.

It enables developers to build scalable SaaS applications with:

* JSON-based code generation
* Multi-tenant architecture (shared or database-per-tenant)
* Automated migrations and rollback
* Tenant provisioning and lifecycle management

---

# ✨ Features

## 🧩 Configuration-Driven Development

Define your application using JSON:

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

## 🏢 Multi-Tenant Architecture

Supports two strategies:

* **Shared Database**
* **Database per Tenant**

---

## ⚙️ Code Generation

Generate:

* Entities
* Repositories
* Migrations

```bash
php bin/ef generate User
php bin/ef generate:all
```

---

## 🗄️ Migration System

* Forward migrations
* Rollback support
* Batch tracking

```bash
php bin/ef migrate
php bin/ef migrate:rollback
```

---

## 🏗️ Tenant Provisioning

Automatically creates:

* Tenant database
* Schema (via migrations)

```bash
php bin/ef tenant:create tenant_1
```

---

## 🧠 Tenant Registry

Central `tenants` table stores:

* tenant_id
* name
* status

---

# 📦 Installation

This will be part of PHP Packagist and will replace the entity-forge ORM.

```bash
composer require vedavith/entity-forge
```

---

# ⚡ Quick Start

## 1. Configure tenancy

### 🟢 Shared Database

```yaml
tenancy:
  enabled: true
  strategy: shared

database:
  driver: mysql
  host: 127.0.0.1
  port: 3306
  database: entity_forge
  username: root
  password: root
```

---

### 🔵 Database per Tenant

```yaml
tenancy:
  enabled: true
  strategy: database

database:
  driver: mysql
  host: 127.0.0.1
  port: 3306
  database: entity_forge
  username: root
  password: root
```

---

## 2. Generate entities

```bash
php bin/ef generate User --migration
php bin/ef migrate
```

---

## 3. Create a tenant

```bash
php bin/ef tenant:create tenant_1
```

---

## 4. Use in application

```php
$app->boot([
    'headers' => ['X-Tenant-ID' => 'tenant_1']
], true);

$repo = new UserRepository($app->getConfig());

$repo->create([
    'name' => 'Ved',
    'email' => 'ved@example.com'
]);

print_r($repo->findAll());
```

---

# ⚙️ Configuration Details

## 🟢 Shared Database Strategy

All tenants share a single database.

### Structure

```
entity_forge
  └── users
        id
        name
        tenant_id
```

### Characteristics

* Uses `tenant_id` for isolation
* Lower infrastructure cost
* Easier to manage

---

## 🔵 Database per Tenant Strategy

Each tenant gets a dedicated database.

### Structure

```
entity_forge                (main DB)
  └── tenants

entity_forge_tenant_1
  └── users

entity_forge_tenant_2
  └── users
```

### Characteristics

* Full data isolation
* No `tenant_id` column required
* Better for enterprise use cases

---

## Tenant Database Naming

```
{base_database}_{tenant_id}
```

Example:

```
entity_forge_tenant_1
entity_forge_tenant_2
```

---

# 🧱 Architecture Overview

```
Application
 ├── Core (boot, config, schema)
 ├── Tenant (context, resolver, provisioning)
 ├── Database (connection, migrations)
 ├── Generator (entity, repository, migration)
 └── Repository (data access layer)
```

---

# 🔄 Tenant Lifecycle

```
Onboard → Create DB → Run Migrations → Register Tenant
```

---

# 🗄️ Database Design

## Main Database

```
entity_forge
  └── tenants
```

## Tenant Databases

```
entity_forge_tenant_1
entity_forge_tenant_2
```

---

# ⚠️ Important Rules

* Always call `boot()` before using repositories
* Never reuse repository instances across tenant switches
* Keep tenant registry in the main database
* Keep application data in tenant databases

---

# 🧪 CLI Commands

```bash
php bin/ef generate User
php bin/ef generate:all
php bin/ef migrate
php bin/ef migrate:rollback
php bin/ef tenant:create tenant_1
```

---

# 🚧 Roadmap

* [ ] Middleware for automatic tenant resolution
* [ ] Dependency Injection container
* [ ] API layer

---

# 🤝 Contributing

Contributions are welcome.
Feel free to open issues or pull requests.

---

# 📄 License

MIT License

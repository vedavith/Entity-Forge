# 🚀 EntityForge

**EntityForge** is an Open source configuration-driven, multi-tenant SaaS framework built in PHP.

It enables you to:

* Generate applications using JSON configs
* Run multi-tenant systems (shared DB or DB-per-tenant)
* Automatically provision tenant infrastructure
* Manage schema with migrations and rollback

---

# ✨ Features

## 🧩 Configuration-Driven Development

Define your application using simple JSON:

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

### 🔹 Shared Database

* Single DB
* Uses `tenant_id` column

### 🔹 Database per Tenant

* Full isolation
* Separate DB per tenant

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

Automatically create:

* Tenant database
* Schema (via migrations)

```bash
php bin/ef tenant:create tenant_1
```

---

## 🧠 Tenant Registry

Central table (`tenants`) tracks:

* tenant_id
* name
* status

---

# 📦 Installation

Once merged, this will be part of entity forge package and will be available on PHP Packagist.

```bash
composer require vedavith/entity-forge
```

---

# ⚡ Quick Start

## 1. Configure

```yaml
tenancy:
  enabled: true
  strategy: database
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

## 4. Use in code

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

# 🧱 Architecture Overview

```text
Application
 ├── Core (boot, config, schema)
 ├── Tenant (context, resolver, provisioning)
 ├── Database (connection, migrations)
 ├── Generator (entity, repository, migration)
 └── Repository (data access layer)
```

---

# 🔄 Tenant Lifecycle

```text
Onboard → Create DB → Run Migrations → Register Tenant
```

---

# 🗄️ Database Structure

## Main DB

```
entity_forge
  └── tenants
```

## Tenant DBs

```
entity_forge_tenant_1
entity_forge_tenant_2
```

---

# ⚠️ Important Rules

* Always boot application before using repositories
* Never reuse repository across tenant switches
* Keep tenant registry in main DB
* Keep user data in tenant DBs

---

# 🧪 Example Commands

```bash
php bin/ef generate User
php bin/ef migrate
php bin/ef tenant:create tenant_1
```

---

# 🚧 Roadmap

* [ ] Middleware (auto tenant resolution)
* [ ] Dependency injection container
* [ ] API layer

---

# 🤝 Contributing

Contributions are welcome. Feel free to open issues or PRs.

---

# 📄 License

MIT License

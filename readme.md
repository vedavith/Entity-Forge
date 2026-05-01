# Entity-Forge

Entity-Forge is a configuration-driven PHP framework for generating entity models and building multi-tenant SaaS applications.

## Core Philosophy

- Configuration over code
- Multi-tenancy by design, not by implementation
- Safe defaults (no accidental data leaks)
- Framework-agnostic

## What It Does

- Generates entity models from configuration
- Provides tenant-aware repositories
- Enforces data isolation automatically

## What It Does NOT Do

- No UI
- No authentication system
- No framework lock-in (Laravel, Symfony, etc.)


## Example Flow (Future)

1. Define entity in JSON
2. Configure tenancy in YAML
3. Run generator
4. Use repository without worrying about tenant logic
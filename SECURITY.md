# Security Policy

## Supported Versions

| Version | Supported |
|---------|-----------|
| 2.x     | ✓ Active  |
| 1.x     | ✗ End of life |

Only the latest `2.x` release receives security fixes.

---

## Reporting a Vulnerability

Please **do not** open a public GitHub issue for security vulnerabilities.

Send a report directly to **veda_ravula@outlook.com** with:

- A description of the vulnerability
- Steps to reproduce it
- The potential impact
- Any suggested fix (optional)

I'll acknowledge your report within **48 hours** and aim to release a patch within **7 days** depending on severity.

---

## Scope

Areas most relevant to this framework:

- **SQL injection** — column name validation in `BaseRepository`, query construction in `MigrationBuilder`
- **Tenant isolation bypass** — `TenantContext`, `TenantGuard`, `BaseRepository::shouldApplyTenantScope()`
- **Arbitrary file write** — `FileWriter` used during code generation
- **JWT validation** — `JwtTenantResolver` (algorithm confusion, signature bypass)
- **Config injection** — YAML parsing and merging in `ConfigLoader`

---

## Disclosure Policy

Once a fix is released, I'll publish a summary of the vulnerability and credit the reporter (unless they prefer to stay anonymous).

---

## Out of Scope

- Vulnerabilities in PHP itself or third-party Composer dependencies
- Issues only reproducible in configurations explicitly documented as insecure
- Denial of service via resource exhaustion in local development setups

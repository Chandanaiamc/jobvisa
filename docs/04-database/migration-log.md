# Migration Log

Tracks database schema changes over time for JobVisa.lk.

| Date | Migration | Type | Notes |
|---|---|---|---|
| 2026-07-11 | `001`–`022` | Baseline schema | Core marketplace tables |
| 2026-07-11 | `023`–`026` | Auth draft | **Archived** → `database/migrations/archive/` |
| 2026-07-11 | `027_create_roles_table` | Auth foundation | Approved; no role seed |
| 2026-07-11 | `028_alter_users_auth_foundation` | Auth foundation | `role_id`, `remember_token`, `deleted_at` |
| 2026-07-11 | `029_create_password_reset_tokens_table` | Auth foundation | `token_hash` |
| 2026-07-11 | `030_create_email_verification_tokens_table` | Auth foundation | Email verification |
| 2026-07-11 | `031_create_login_attempts_table` | Auth foundation | Brute-force analytics |

## Why an archive folder exists

See [`migration-archive.md`](./migration-archive.md). Draft auth migrations `023`–`026` were superseded by `027`–`031` and moved to `database/migrations/archive/` so they are not applied by mistake.

## Baseline execution order

```text
001 → 002 → … → 022
```

## Active authentication upgrade order

```text
027 → 028 → 029 → 030 → 031
```

Requires `users` from `008`.

## Authentication rollback order

```text
031 → 030 → 029 → 028 → 027
```

## Do not run

```text
database/migrations/archive/023–026
```

## Documentation

- Archive policy: `docs/04-database/migration-archive.md`
- Historical draft notes: `docs/04-database/migrations/023_*.md` … `026_*.md` (superseded)

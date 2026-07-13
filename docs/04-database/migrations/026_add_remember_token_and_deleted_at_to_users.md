# Migration 026 — Add `remember_token` and `deleted_at` to `users`

| Field | Value |
|---|---|
| **ID** | `026_add_remember_token_and_deleted_at_to_users` |
| **Type** | Alter table + selective backfill |
| **Compatibility** | MariaDB 10.4+ |
| **Rollback** | `026_add_remember_token_and_deleted_at_to_users_rollback.sql` |
| **Depends on** | `008_create_users_table` |

## Purpose

1. Enable **remember-me** persistent login via `remember_token`.
2. Enable proper **soft delete** via `deleted_at` (in addition to existing `status`).

## What changes

- Adds `remember_token VARCHAR(100) NULL` + index.
- Adds `deleted_at DATETIME(3) NULL` + indexes `(deleted_at)`, `(status, deleted_at)`.
- Backfills `deleted_at` from `updated_at` where `status = 'deleted'`.

## MariaDB 10.4 soft-delete + email uniqueness

MariaDB 10.4 does **not** support PostgreSQL-style partial unique indexes (`UNIQUE email WHERE deleted_at IS NULL`).

**Policy (application-enforced):**

- Keep `uq_users_email` on `email`.
- On soft delete: set `deleted_at = NOW(3)`, set `status = 'deleted'`, and **anonymize** email (e.g. `deleted+{id}@invalid.local`) if re-registration with the same address must be allowed.
- All auth queries must add `deleted_at IS NULL`.

## Remember-token guidance

- Store a random opaque string; rotate on logout and password change.
- Never store passwords in this column.

## Rollback

Drops indexes and both columns. Does not restore anonymized emails.

## Verification

```sql
SHOW COLUMNS FROM users LIKE 'remember_token';
SHOW COLUMNS FROM users LIKE 'deleted_at';
SELECT COUNT(*) AS soft_deleted FROM users WHERE deleted_at IS NOT NULL;
```

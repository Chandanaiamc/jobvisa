# Migration 024 — Add `users.role_id` foreign key

| Field | Value |
|---|---|
| **ID** | `024_add_role_id_to_users_table` |
| **Type** | Alter table + data backfill |
| **Compatibility** | MariaDB 10.4+ |
| **Rollback** | `024_add_role_id_to_users_table_rollback.sql` |
| **Depends on** | `008_create_users_table`, `023_create_roles_table` |

## Purpose

Replace string-based role authority with a proper foreign key to `roles.id`, while **preserving** the legacy `users.role` column for compatibility and auditability during transition.

## What changes

1. Adds nullable `role_id`.
2. Backfills `role_id` by joining `users.role` → `roles.slug`.
3. Defaults unmatched rows to `seeker`.
4. Sets `role_id` NOT NULL, adds indexes, adds `fk_users_role` (`ON DELETE RESTRICT`).

## Preserved structures

- Legacy column `users.role` remains.
- Existing indexes on `role` / `status` remain.
- No application routes or PHP files are modified by this migration pack.

## Application contract (going forward)

- **Source of truth:** `users.role_id`
- Legacy `users.role` may be kept in sync by application code until a future cleanup migration drops it.

## Rollback

Drops FK, indexes, and `role_id`. Does not modify `roles` or legacy `role`.

## Verification

```sql
SELECT u.id, u.email, u.role, u.role_id, r.slug
FROM users u
JOIN roles r ON r.id = u.role_id
LIMIT 20;

SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'users'
  AND CONSTRAINT_NAME = 'fk_users_role';
```

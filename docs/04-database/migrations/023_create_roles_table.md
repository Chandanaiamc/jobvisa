# Migration 023 — Create `roles` table

| Field | Value |
|---|---|
| **ID** | `023_create_roles_table` |
| **Type** | Create table + reference seed |
| **Engine** | InnoDB |
| **Charset** | utf8mb4 / utf8mb4_unicode_ci |
| **Compatibility** | MariaDB 10.4+ |
| **Rollback** | `023_create_roles_table_rollback.sql` |
| **Depends on** | None |
| **Required by** | `024_add_role_id_to_users_table` |

## Purpose

Introduce a normalized **roles** master table so authentication and authorization no longer depend on unconstrained `VARCHAR` role strings.

## What changes

- Creates `roles` with `BIGINT UNSIGNED` PK, unique `slug` / `name`, `is_system` flag, timestamps.
- Seeds four system roles idempotently: `seeker`, `employer`, `admin`, `staff`.

## Why system seed is included

`users.role_id` (migration 024) requires stable role rows. This seed is **platform reference data**, not demo/business sample data.

## Security / governance notes

- `is_system = 1` marks roles that application code should not delete.
- Slugs are immutable identifiers for application mapping.

## Rollback

Drops `roles`. **Must** run `024` rollback first if `fk_users_role` exists.

## Verification

```sql
SHOW CREATE TABLE roles;
SELECT slug, name, is_system FROM roles ORDER BY id;
```

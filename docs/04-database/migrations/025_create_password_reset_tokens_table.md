# Migration 025 — Create `password_reset_tokens`

| Field | Value |
|---|---|
| **ID** | `025_create_password_reset_tokens_table` |
| **Type** | Create table |
| **Compatibility** | MariaDB 10.4+ |
| **Rollback** | `025_create_password_reset_tokens_table_rollback.sql` |
| **Depends on** | `008_create_users_table` |

## Purpose

Provide durable, auditable storage for **password reset** challenges without storing raw tokens.

## Columns

| Column | Role |
|---|---|
| `email` | Target mailbox (indexed) |
| `user_id` | Optional FK to `users` (`ON DELETE SET NULL`) |
| `token` | **CHAR(64)** — SHA-256 hex hash of the raw token |
| `expires_at` | Hard expiry |
| `used_at` | Single-use marker |
| `requested_ip` | Abuse forensics |

## Security rules for application layer

1. Generate a high-entropy raw token; email the raw value once.
2. Store only `hash('sha256', $rawToken)` (or equivalent) in `token`.
3. Reject rows where `expires_at < NOW(3)` or `used_at IS NOT NULL`.
4. Invalidate prior unused tokens for the same email on new request.

## Rollback

`DROP TABLE password_reset_tokens`.

## Verification

```sql
SHOW CREATE TABLE password_reset_tokens;
```

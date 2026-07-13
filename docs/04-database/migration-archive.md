# Migration Archive Policy

## Why `database/migrations/archive/` exists

JobVisa.lk briefly had **two overlapping authentication migration sets**:

| Set | Numbers | Status |
|---|---|---|
| Draft auth pass | `023`–`026` | **Archived** — superseded |
| Approved auth foundation | `027`–`031` | **Active** — use these |

The draft set (`023`–`026`) introduced roles, `role_id`, password-reset tokens, remember-token, and soft-delete columns, but used different column shapes, included role **seed** data, and conflicted with the later approved foundation.

The approved set (`027`–`031`) is the authoritative authentication schema work:

- Normalized `roles` (no seed in migration)
- Safe `users` auth columns (`role_id`, `remember_token`, `deleted_at`)
- `password_reset_tokens` with `token_hash`
- `email_verification_tokens`
- `login_attempts`

Archiving (not deleting) preserves history if any environment already inspected or partially applied the draft files, while preventing operators from running the wrong set.

## Archive contents

```text
database/migrations/archive/
  023_create_roles_table.sql
  023_create_roles_table_rollback.sql
  024_add_role_id_to_users_table.sql
  024_add_role_id_to_users_table_rollback.sql
  025_create_password_reset_tokens_table.sql
  025_create_password_reset_tokens_table_rollback.sql
  026_add_remember_token_and_deleted_at_to_users.sql
  026_add_remember_token_and_deleted_at_to_users_rollback.sql
```

**Do not execute archived migrations** on new environments.

## Related documentation

Draft notes under `docs/04-database/migrations/023_*.md` … `026_*.md` describe the archived SQL and are historical only. Prefer this file and the active `027`–`031` SQL for current work.

## Active authentication execution order

See `docs/04-database/migration-log.md`.

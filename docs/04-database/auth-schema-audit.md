# Authentication Schema Audit Report

**Project:** JobVisa.lk  
**Date:** 11 July 2026  
**Auditor:** Senior Database Architect  
**Scope:** Authentication-related MySQL migrations only  
**Constraint:** No SQL was created or modified. Approval required before corrections.

---

## Executive summary

JobVisa.lk has a **usable identity core** (`users` + `user_profiles`) with email uniqueness, adequate password hash length, account status, and an email-verified timestamp. It does **not** yet meet enterprise authentication schema standards: there is **no `roles` table**, role is a free-form string, and there is **no support** for password-reset tokens, remember tokens, or proper soft deletes (`deleted_at`).

**Final score: 61 / 100**

---

## 1. Existing structure

### `users` (`008_create_users_table.sql`)

| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT UNSIGNED AI PK | Correct |
| `email` | VARCHAR(191) NOT NULL | Unique index `uq_users_email` |
| `password_hash` | VARCHAR(255) NOT NULL | Suitable for bcrypt / Argon2id |
| `full_name` | VARCHAR(150) NOT NULL | |
| `phone` | VARCHAR(32) NULL | Indexed |
| `role` | VARCHAR(32) NOT NULL DEFAULT `seeker` | Comment: seeker\|employer\|admin\|staff — **not an FK** |
| `status` | VARCHAR(32) NOT NULL DEFAULT `active` | Comment: active\|pending\|suspended\|deleted |
| `email_verified_at` | DATETIME(3) NULL | Verification *state* only |
| `phone_verified_at` | DATETIME(3) NULL | |
| `last_login_at` | DATETIME(3) NULL | |
| `last_login_ip` | VARCHAR(45) NULL | IPv4/IPv6 |
| `created_at` / `updated_at` | DATETIME(3) | Present |

**Indexes:** PK, unique email, `(role, status)`, `phone`, `created_at`

### `user_profiles` (`009_create_user_profiles_table.sql`)

| Aspect | Status |
|---|---|
| 1:1 with users | Yes — `UNIQUE (user_id)` |
| FK to users | `ON DELETE CASCADE ON UPDATE CASCADE` |
| Geo FKs | nationality / preferred → `countries`; current → `cities` (`ON DELETE SET NULL`) |
| Timestamps | `created_at`, `updated_at` present |
| Auth fields | None (correct — profile is not auth) |

### Related auth-adjacent tables

| Table | Auth relevance |
|---|---|
| `employers` | Links `user_id` uniquely to employer account |
| `audit_logs` | Optional `actor_user_id` |
| `roles` / `permissions` / `role_user` | **Do not exist** |
| `password_reset_tokens` | **Does not exist** |
| `email_verification_tokens` | **Does not exist** |
| `sessions` / `personal_access_tokens` | **Do not exist** |

---

## 2. Checklist review (areas 1–15)

| # | Area | Finding | Grade |
|---|---|---|---|
| 1 | `users` table | Present and generally sound | Pass |
| 2 | `user_profiles` table | Present; clean 1:1 | Pass |
| 3 | `roles` table | **Missing** | Fail |
| 4 | Users linked to roles | **No** — `users.role` is VARCHAR, not FK | Fail |
| 5 | Email uniqueness | `UNIQUE (email)` on VARCHAR(191) | Pass |
| 6 | Password storage length | VARCHAR(255) adequate | Pass |
| 7 | Account status | `status` column present | Partial |
| 8 | Email verification | `email_verified_at` only; no token table | Partial |
| 9 | Password reset support | **Missing** | Fail |
| 10 | Remember token support | **Missing** | Fail |
| 11 | Soft delete support | Status value `deleted` only; no `deleted_at` | Partial |
| 12 | Useful indexes | Good baseline; a few gaps | Pass+ |
| 13 | Foreign key rules | Profiles/geo OK; no role FK | Partial |
| 14 | MariaDB compatibility | Generally compatible (see notes) | Pass |
| 15 | Migration execution order | `001/002` before `009`; `008` before `009` — correct | Pass |

---

## 3. Missing tables

| Missing table | Purpose |
|---|---|
| `roles` | Canonical role definitions (seeker, employer, admin, staff) |
| `permissions` (recommended) | Fine-grained capabilities for admin/staff |
| `role_permissions` (recommended) | Role ↔ permission map |
| `role_user` **or** `users.role_id` FK | Proper user ↔ role link (prefer `role_id` if single-role; pivot if multi-role) |
| `password_reset_tokens` | Email + hashed token + expiry for forgot-password |
| `email_verification_tokens` (or reuse a generic `user_tokens`) | Secure verify links (optional if using signed URLs only) |
| `sessions` (optional) | Server-side session store at scale |
| `login_attempts` / `auth_events` (optional) | Rate-limit and forensic audit of auth |

---

## 4. Missing columns (on `users` / related)

| Column / change | Why |
|---|---|
| `role_id` BIGINT UNSIGNED NULL/NOT NULL FK → `roles.id` | Replace or accompany string `role` |
| `deleted_at` DATETIME(3) NULL | True soft delete; query filters; unique-email strategy |
| `remember_token` VARCHAR(100) NULL | “Remember me” persistent login |
| `password_changed_at` DATETIME(3) NULL | Force re-login / credential hygiene |
| `failed_login_attempts` INT UNSIGNED DEFAULT 0 | Lockout support |
| `locked_until` DATETIME(3) NULL | Temporary lock after abuse |
| On `password_reset_tokens`: `email`, `token` (hashed), `created_at`, `expires_at` | Reset flow |

**Note:** Keeping both `role` (legacy string) and `role_id` during migration is acceptable temporarily; long-term prefer `role_id` only.

---

## 5. Security concerns

1. **No password-reset table** — cannot implement secure forgot-password without storing hashed, expiring tokens.  
2. **No remember-token column** — persistent login cannot be implemented safely in-schema.  
3. **Role as unconstrained VARCHAR** — application can write arbitrary roles; no referential integrity; harder to audit.  
4. **Soft delete via `status='deleted'` + unique email** — a deleted user blocks re-registration with the same email forever (or forces hard delete). Prefer `deleted_at` + partial unique strategy (e.g. unique on `(email)` where `deleted_at IS NULL`, or email mutation on delete).  
5. **Email verification incomplete** — `email_verified_at` records outcome but schema has nowhere for one-time verification secrets (unless app uses only signed URLs with no DB).  
6. **No lockout fields** — credential stuffing harder to mitigate at DB level.  
7. **`password_hash` name is good** — avoids implying plaintext `password`; keep application using `password_hash()` / Argon2id.  
8. **CASCADE delete on `user_profiles`** — correct for owned profile; ensure application soft-deletes users rather than hard-deleting when retention/audit matters (hard delete cascades to resumes/applications via other FKs).

---

## 6. Relationship concerns

```
CURRENT:
  users.role  (string)     ←── no FK ──→  (no roles table)
  users 1 ──1 user_profiles
  user_profiles N ──1 countries / cities

RECOMMENDED (single role per user):
  roles 1 ──* users (users.role_id)
  users 1 ──1 user_profiles

RECOMMENDED (multi-role later):
  users * ──* roles  via role_user
```

- Employer capability is partly modeled in `employers.user_id` (good) but overlaps conceptually with `users.role = 'employer'` — must stay consistent in application rules.  
- Admin/staff have no separate privilege model beyond the string role.  
- `user_profiles` depends on `countries`/`cities` created in migrations `001`/`002` — order is correct.

---

## 7. MariaDB compatibility

| Feature used | MariaDB notes |
|---|---|
| InnoDB + utf8mb4_unicode_ci | Supported |
| BIGINT UNSIGNED AI | Supported |
| DATETIME(3) fractional seconds | Supported (MariaDB 5.3+ / 10.x) |
| `ON UPDATE CURRENT_TIMESTAMP(3)` | Supported |
| JSON columns (other tables) | MariaDB 10.2+ |
| FULLTEXT on InnoDB (`jobs`) | MariaDB 10.0.5+ |
| Comments on columns | Supported |

**Verdict:** Auth tables as written are MariaDB-friendly. Prefer testing on the exact MariaDB/MySQL version in production. Avoid MySQL-only features (none critical in auth tables).

---

## 8. Migration execution order (auth-relevant)

| Order | Migration | Dependency |
|---|---|---|
| 001 | `countries` | — |
| 002 | `cities` | countries |
| 008 | `users` | — |
| 009 | `user_profiles` | users, countries, cities |

**Assessment:** Current order is valid.  
**If adding `roles`:** insert **before** `users` (e.g. `007b` / renumber) **or** add `role_id` in a later alter migration after backfill. Do not add FK to `roles` until `roles` exists and is seeded.

---

## 9. Recommended corrections (awaiting approval)

### P0 — required for production auth
1. Add `roles` table (`id`, `name`, `slug` UNIQUE, timestamps).  
2. Add `users.role_id` FK → `roles.id` (keep `role` temporarily or migrate data then drop).  
3. Add `password_reset_tokens` table (hashed token, email/user_id, expires_at).  
4. Add `users.deleted_at` and define email uniqueness policy for soft-deleted accounts.  
5. Add `users.remember_token` VARCHAR(100) NULL.

### P1 — hardening
6. Add `failed_login_attempts`, `locked_until`.  
7. Add `password_changed_at`.  
8. Add index `idx_users_status` and/or `(status, deleted_at)`.  
9. Optional `email_verification_tokens` if not using stateless signed URLs.  
10. Document single source of truth: `users.role_id` vs `employers` row for employer capability.

### P2 — enterprise RBAC
11. `permissions` + `role_permissions`.  
12. Optional multi-role `role_user` if staff need multiple hats.  
13. Auth event / login attempt log table.

---

## 10. Final score (out of 100)

| Category | Max | Score | Notes |
|---|---:|---:|---|
| Users core columns | 15 | 12 | Solid; missing reset/remember/soft-delete columns |
| User profiles | 10 | 10 | Clean 1:1 + FKs |
| RBAC / roles linkage | 15 | 3 | String role only |
| Email uniqueness | 8 | 7 | Unique OK; soft-delete conflict risk |
| Password storage | 8 | 8 | VARCHAR(255) + hash column naming |
| Account status | 6 | 4 | Present; overlaps soft delete |
| Email verification | 6 | 3 | Timestamp only |
| Password reset | 8 | 0 | Missing |
| Remember token | 4 | 0 | Missing |
| Soft delete | 5 | 2 | Status flag only |
| Indexes | 5 | 4 | Good; minor gaps |
| Foreign keys | 5 | 3 | Profiles OK; no role FK |
| MariaDB compatibility | 3 | 3 | Compatible |
| Migration order | 2 | 2 | Correct for current set |
| **Total** | **100** | **61** | |

### Score interpretation
| Range | Meaning |
|---|---|
| 85–100 | Production-ready auth schema |
| 70–84 | Minor gaps |
| 55–69 | Usable core; not auth-complete |
| &lt;55 | Unsafe to build auth on |

**Verdict:** Safe to build **registration/login prototypes** against `users`, but **not** ready for full enterprise auth (reset, remember-me, RBAC, soft delete) without the P0 corrections above.

---

## Approval gate

**No files were modified.**

Awaiting approval to implement P0 (and optionally P1) auth schema migrations.

---

*End of authentication schema audit.*

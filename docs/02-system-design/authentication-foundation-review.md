# Authentication Foundation — Pre-Development Review

**Project:** JobVisa.lk Enterprise  
**Document type:** Gate review (mandatory before auth implementation)  
**Audience:** Engineering, Security, Product, QA  
**Status:** Awaiting approval — **do not implement authentication until checklist is signed off**  
**Related:** `docs/04-database/auth-schema-audit.md`, migrations `023`–`026`, MVC framework audit  

---

## 1. Architecture Review

### 1.1 Authentication module boundaries

Authentication must be a **bounded module** inside the existing pure PHP MVC foundation — not scattered across controllers.

| Layer | Responsibility | Must not |
|---|---|---|
| HTTP (routes/controllers) | Accept input, call services, return views/JSON | Contain hashing, SQL, or policy rules |
| Middleware | Gate requests (guest/auth/role/CSRF) | Own business workflows |
| Services | Register, login, logout, reset, verify, lockout | Echo HTML or read `$_POST` directly |
| Repositories | Persist/retrieve users, tokens, sessions, attempts | Know about HTTP |
| Models | Entity shape + light helpers via `BaseModel` | Run multi-step auth flows |
| Views | Forms + flash messages only | Call repositories |

**Recommended namespace layout (future code — not created yet):**

```text
app/controllers/Auth/          AuthController, PasswordResetController, …
app/Services/Auth/             AuthService, PasswordResetService, …
app/Repositories/Auth/         UserRepository, PasswordResetRepository, …
app/middleware/                AuthMiddleware, GuestMiddleware, CsrfMiddleware, RoleMiddleware
app/Auth/                      SessionGuard, CsrfToken, RateLimiter (or under Core)
app/views/auth/                login, register, forgot, reset, verify
```

Public site, admin, and API must share **one** auth domain core; only entry points differ.

### 1.2 Controllers

| Controller (planned) | Actions | Notes |
|---|---|---|
| `AuthController` | showLogin, login, showRegister, register, logout | Web session auth |
| `PasswordResetController` | showForgot, sendLink, showReset, reset | Uses `password_reset_tokens` |
| `EmailVerificationController` | notice, verify, resend | Needs token table (gap) |
| `HealthController` | existing `/health/database` | Out of auth scope; leave untouched |

Controllers stay thin: validate → service → redirect/view.

### 1.3 Services

| Service (planned) | Duties |
|---|---|
| `AuthService` | Register, attempt login, logout, regenerate session |
| `PasswordHasher` | Wrap `password_hash` / `password_verify` (Argon2id preferred) |
| `PasswordResetService` | Issue/consume hashed reset tokens |
| `EmailVerificationService` | Issue/consume verification tokens |
| `LockoutService` | Track attempts; enforce lock windows |
| `SessionAuthService` | Bind user id to session; “remember me” cookie handling |

### 1.4 Repositories

| Repository (planned) | Tables |
|---|---|
| `UserRepository` | `users`, soft-delete filters (`deleted_at IS NULL`) |
| `UserProfileRepository` | `user_profiles` on register |
| `RoleRepository` | `roles` lookup by slug/id |
| `PasswordResetRepository` | `password_reset_tokens` |
| `EmailVerificationRepository` | `email_verification_tokens` *(to be added)* |
| `LoginAttemptRepository` | `login_attempts` *(to be added)* |
| `UserSessionRepository` | `user_sessions` *(to be added, if DB sessions)* |
| `AuditLogRepository` | `audit_logs` for auth events |

All SQL via PDO prepared statements through `Database` / `BaseModel` patterns.

### 1.5 Models

| Model (planned) | Extends | Notes |
|---|---|---|
| `User` | `BaseModel` | Never expose `password_hash` in `toArray()` for views/API |
| `Role` | `BaseModel` | System roles from migration 023 |
| `UserProfile` | `BaseModel` | Created with seeker/employer registration |
| Token/attempt models | `BaseModel` | Optional; repositories may suffice initially |

**Source of truth for authorization:** `users.role_id` → `roles` (legacy `users.role` string is transitional only).

### 1.6 Middleware

| Middleware (planned) | Behaviour |
|---|---|
| `GuestMiddleware` | Redirect authenticated users away from login/register |
| `AuthMiddleware` | Require logged-in user; redirect or 401 JSON |
| `RoleMiddleware` | Require one or more role slugs (`admin`, `employer`, …) |
| `CsrfMiddleware` | Validate token on POST/PUT/PATCH/DELETE for web |
| `ThrottleMiddleware` | Rate-limit login/forgot/register endpoints |

Pipeline must be wired in the framework kernel **before** controller dispatch (framework extension — separate from feature code).

### 1.7 Sessions

| Topic | Decision for review |
|---|---|
| Driver (phase 1) | PHP native file sessions under `storage/sessions/` (outside web root) |
| Driver (scale) | DB `user_sessions` and/or Redis — interface behind `SessionStore` |
| Session keys | e.g. `auth.user_id`, `auth.role_id`, `auth.login_at` |
| Regeneration | On every successful login and privilege change |
| Admin isolation | Separate cookie name for admin later (`jobvisa_admin_session`) |
| Remember me | `users.remember_token` + long-lived cookie; rotate on use/logout |

### 1.8 Validation

| Concern | Approach |
|---|---|
| Layer | Dedicated validators or Form Request objects called from controllers |
| Register | email format/unique (active users), password strength, name, role intent |
| Login | email + password required; no user enumeration in messages |
| Reset | email exists check without leaking; token format/expiry |
| Rules | Server-side only; client-side is UX aid |

### 1.9 Routing

**Do not modify existing working routes** (`/`, `/about`, `/jobs`, …, `/health/database`).

Planned **additive** auth routes (implementation phase only):

| Method | Path | Action |
|---|---|---|
| GET/POST | `/login` | Login |
| GET/POST | `/register` | Register |
| POST | `/logout` | Logout |
| GET/POST | `/password/forgot` | Request reset |
| GET/POST | `/password/reset/{token}` | Perform reset |
| GET | `/email/verify/{token}` | Verify email |
| POST | `/email/verification-notification` | Resend |

Admin auth routes under `/admin/...` later; API under `/api/v1/auth/...`.

### 1.10 Separation of concerns

- **Web** uses sessions + CSRF + HTML forms.  
- **API** uses bearer/token auth (phase 2) — same `AuthService` password verify, different guard.  
- **Employer capability** = `role_id` (employer) **and/or** row in `employers` — document single rule before coding.  
- No auth SQL inside views; no session writes inside repositories.

### 1.11 API readiness

| Requirement | Prep now |
|---|---|
| Versioned paths | `/api/v1/auth/*` |
| Stateless option | Personal access tokens or JWT later; design `AuthService` without hard-coding `$_SESSION` |
| Error envelope | Consistent JSON `{ error: { code, message } }` |
| CORS | Config allowlist when API goes public |
| Throttling | Per IP + per account |

### 1.12 Mobile app readiness

| Requirement | Prep now |
|---|---|
| Token login | Same credentials → issue refresh/access tokens |
| Secure storage | App stores tokens; never embed secrets in APK/IPA |
| Email verify / reset | Deep links to mobile or universal HTTPS pages |
| Device/session list | `user_sessions` enables remote revoke |
| RBAC | Return `role` slug + permissions claim in token payload later |

---

## 2. Database Review

### 2.1 `roles` — **present** (migration 023)

System rows: `seeker`, `employer`, `admin`, `staff`.  
**Gap:** no `permissions` / `role_permissions` yet (acceptable for MVP if role slug checks suffice).

### 2.2 `permissions` — **missing**

Needed for fine-grained admin/staff capabilities (`jobs.moderate`, `users.suspend`, …).  
**Decision:** defer to post-MVP **or** add before admin panel — must be checked in Approval Checklist.

### 2.3 `users` — **present** + auth upgrades 024/026

| Item | Status |
|---|---|
| `role_id` FK → `roles` | Present (024); legacy `role` retained |
| `password_hash` VARCHAR(255) | Present |
| `email` UNIQUE VARCHAR(191) | Present |
| `status` | Present |
| `email_verified_at` | Present |
| `remember_token` | Present (026) |
| `deleted_at` | Present (026) |
| Lockout columns (`failed_login_attempts`, `locked_until`) | **Missing** |
| `password_changed_at` | **Missing** |

### 2.4 `user_profiles` — **present** (009)

1:1 with users; create empty/minimal profile on registration.

### 2.5 `password_reset_tokens` — **present** (025)

Hashed `token` CHAR(64), `expires_at`, `used_at`, optional `user_id`.  
**App rule:** store SHA-256 (or stronger) hash only.

### 2.6 `email_verification_tokens` — **missing**

Required unless verification uses **stateless signed URLs only**.  
Enterprise preference: DB table mirroring reset tokens (hash, expiry, used_at).

### 2.7 `user_sessions` — **missing**

Optional for phase 1 (file sessions). Required for multi-device revoke and horizontal scale tracking.

### 2.8 `login_attempts` — **missing**

Recommended for brute-force analytics and lockout without overloading `users`.

### 2.9 `audit_logs` — **present** (022)

Use for `auth.login`, `auth.logout`, `auth.failed`, `auth.password_reset`, `auth.lockout`.  
Never log passwords or raw tokens.

### 2.10 Foreign keys

| Relationship | Status | Rule |
|---|---|---|
| `users.role_id` → `roles.id` | Present | `ON DELETE RESTRICT` |
| `user_profiles.user_id` → `users.id` | Present | `ON DELETE CASCADE` |
| `password_reset_tokens.user_id` → `users.id` | Present | `ON DELETE SET NULL` |
| Future token/attempt/session FKs | Pending | Prefer `ON DELETE CASCADE` for owned auth rows |

### 2.11 Indexes

| Area | Status | Notes |
|---|---|---|
| `users.email` unique | OK | Soft-delete re-register needs email anonymization (MariaDB 10.4) |
| `users (role_id, status)` | OK | |
| `users.deleted_at` | OK | Always filter in auth queries |
| `password_reset_tokens` email/token/expires | OK | |
| Future `login_attempts (email, created_at)` / `(ip, created_at)` | Needed with table | |
| Future `user_sessions (user_id, last_activity)` | Needed with table | |

### 2.12 Soft deletes

- Column `deleted_at` exists; `status='deleted'` also exists — **pick one primary signal** (`deleted_at`) and keep status in sync in service layer.  
- MariaDB 10.4: no partial unique indexes — anonymize email on soft delete if reuse required.

### 2.13 Migration order (auth-relevant)

```text
001 countries → 002 cities → 008 users → 009 user_profiles
023 roles → 024 users.role_id → 025 password_reset_tokens → 026 remember_token + deleted_at
022 audit_logs (independent; already before or after — ensure exists before logging)
```

**Pending migrations (if approved):**  
`027_create_email_verification_tokens`  
`028_create_login_attempts`  
`029_create_user_sessions` (optional phase)  
`030_add_lockout_columns_to_users`  
`031_create_permissions_tables` (optional)

---

## 3. Security Review

### 3.1 `password_hash` and `password_verify`

- Use `PASSWORD_ARGON2ID` when available; fallback `PASSWORD_BCRYPT`.  
- Never invent custom crypto.  
- Rehash transparently if `password_needs_rehash()`.

### 3.2 CSRF protection

- Token per session; validate on all state-changing web routes.  
- Prefer middleware; forms include hidden field; reject mismatch with 419-style response.

### 3.3 Session fixation prevention

- `session_regenerate_id(true)` on login and role elevation.  
- Invalidate remember token on password change.

### 3.4 Secure session cookies

| Flag | Value |
|---|---|
| `HttpOnly` | true |
| `Secure` | true in production (HTTPS) |
| `SameSite` | `Lax` or `Strict` |
| Cookie name | Distinct from admin later |
| Path | Application base path |

### 3.5 Brute-force protection

- Record failures in `login_attempts` (planned).  
- Progressive delay / lockout after N failures per email and per IP.

### 3.6 Rate limiting

- Login, register, forgot-password, verify-resend.  
- Storage: file/cache initially; Redis later.  
- Return generic messages; HTTP 429 when exceeded.

### 3.7 Email verification

- Block sensitive actions until `email_verified_at` is set (policy decision).  
- Tokens hashed at rest; single-use; short TTL.

### 3.8 Password reset token hashing

- Already designed in 025 — enforce in service.  
- TTL e.g. 60 minutes; mark `used_at`; delete/ignore expired.

### 3.9 Account lockout rules (proposed)

| Rule | Proposal |
|---|---|
| Threshold | 5 failed attempts / 15 minutes / identity |
| Lock duration | 15–30 minutes (`locked_until`) |
| Admin unlock | Via admin + audit log |
| Reset password | Clears lock |

### 3.10 Input validation

- Strict types; allowlists; max lengths matching DB.  
- Normalize email (`strtolower` + trim).

### 3.11 Output escaping

- All view output via `e()` / `htmlspecialchars`.  
- Never reflect raw tokens in HTML.

### 3.12 SQL injection protection

- PDO prepared statements only (`Database::query`).  
- No string-concatenated SQL in auth repositories.

### 3.13 Role-based access control

- Authorize with `role_id` / role slug from DB on each request (session cache OK if revalidated).  
- Never trust hidden form fields for role.  
- Employer routes also verify `employers` row when required.

### 3.14 Audit logging

- Success/failure auth events to `audit_logs` (and/or `storage/logs/auth/`).  
- Include `actor_user_id` when known, IP, user agent; redact secrets.

---

## 4. Performance Review

### 4.1 Indexes

Auth hot path: lookup by `email` + `deleted_at IS NULL`.  
Ensure queries are sargable; avoid `LOWER(email)` without normalized storage.

### 4.2 Session storage strategy

| Phase | Strategy |
|---|---|
| 1 | Files in `storage/sessions` |
| 2 | Database `user_sessions` for revoke/audit |
| 3 | Redis for multi-node sticky-less sessions |

Abstract behind an interface early.

### 4.3 Query efficiency

- Login: one user select by email; one role join or cached slug.  
- Avoid loading full profile/resumes on login.  
- Reset: indexed token hash lookup (unique).

### 4.4 Caching opportunities

- Role slug by `role_id` (tiny, in-process or Redis).  
- Rate-limit counters.  
- Do **not** cache password hashes in shared cache.

### 4.5 Rate-limit storage

- Phase 1: cache files or DB counters.  
- Phase 2: Redis `INCR` + TTL (preferred at scale).

### 4.6 Unnecessary joins

- Don’t join `user_profiles`, `employers`, payments on every request.  
- Lazy-load context in middleware only when needed.

### 4.7 Future Redis compatibility

Design:

```text
RateLimiterInterface
SessionStoreInterface
```

File/DB implementations first; Redis drop-in later without rewriting AuthService.

### 4.8 Scalability for one million users

| Concern | Mitigation |
|---|---|
| Login QPS | Indexed email; Redis rate limits; read replica for non-auth reads |
| Session volume | Redis or sticky sessions + central store |
| Token tables growth | TTL cleanup job; partition by `created_at` later |
| Audit logs | Async write; partition/archive |
| Soft deletes | Always filter `deleted_at`; periodic hard-delete policy |

---

## 5. Documentation Requirements

Before/during implementation, maintain:

| Doc | Location / content |
|---|---|
| Routes | `docs/05-api/` + web route list in module doc |
| Controllers | `docs/03-modules/` auth module — actions & inputs |
| Services | Method contracts, side effects, events logged |
| Repositories | Queries owned; soft-delete conventions |
| Migrations | `docs/04-database/migrations/*` (continue pattern of 023–026) |
| Security rules | Update `docs/02-system-design/security-architecture.md` |
| Test cases | `docs/07-testing/test-cases.md` — register/login/reset/lockout/CSRF |
| Rollback | Per-migration `*_rollback.sql` + order in migration-log |
| Changelog | `docs/09-project-management/changelog.md` entry per release |

Also update `docs/09-project-management/decisions-log.md` for: Argon2id, session driver, lockout thresholds, email-verify enforcement, employer = role vs `employers` row.

---

## 6. Implementation Order

Numbered plan from schema completion to final testing. **No coding until Section 7 is approved.**

1. **Decision freeze** — Sign Approval Checklist (Section 7).  
2. **Schema gaps** — Migrations for `email_verification_tokens`, `login_attempts`, optional lockout columns, optional `user_sessions` / permissions.  
3. **Apply migrations** on local MariaDB; verify with `/health/database` + SQL checks.  
4. **Framework hooks (minimal)** — Middleware pipeline + session bootstrap + CSRF helper (no feature UI yet if preferred as separate PR).  
5. **Repositories** — User, Role, PasswordReset, (Verification), LoginAttempt.  
6. **Services** — PasswordHasher, AuthService, PasswordResetService, LockoutService.  
7. **Middleware** — Guest, Auth, Csrf, Throttle, Role.  
8. **Controllers + views** — Register, login, logout (seeker first).  
9. **Password reset flow** — Forgot + reset forms; email queue stub/log in local.  
10. **Email verification flow** — If required by policy.  
11. **Remember-me** — Optional cookie; secure flags.  
12. **Audit + auth logging** — `audit_logs` + `storage/logs/auth`.  
13. **Employer registration path** — Align `role_id` + `employers`/`companies` rules (may be phase 1b).  
14. **API auth skeleton** — `/api/v1/auth/login` returning JSON (optional parallel track).  
15. **Automated tests** — PHPUnit feature tests for happy paths + lockout + CSRF failure.  
16. **Security pass** — Manual checklist (cookies, enumeration, reset abuse).  
17. **Docs + changelog** — Complete Section 5 artifacts.  
18. **Staging deploy + smoke** — Register/login/logout/reset on staging.  
19. **Sign-off** — Security + Tech Lead approval to build product features on top.

---

## 7. Approval Checklist

Check every item before authentication coding begins.

### Architecture
- [ ] Auth module boundaries (controller / service / repository) approved  
- [ ] Middleware list and pipeline placement approved  
- [ ] Session driver for phase 1 approved (files vs DB)  
- [ ] Employer identity rule approved (`role_id` vs `employers` row)  
- [ ] API/mobile token strategy deferred or scoped for phase 1  

### Database
- [ ] Existing migrations `023`–`026` applied (or scheduled) on local  
- [ ] Decision: add `email_verification_tokens` — **Yes / No**  
- [ ] Decision: add `login_attempts` — **Yes / No**  
- [ ] Decision: add lockout columns on `users` — **Yes / No**  
- [ ] Decision: add `user_sessions` in phase 1 — **Yes / No**  
- [ ] Decision: add `permissions` RBAC in phase 1 — **Yes / No**  
- [ ] Soft-delete + email anonymization policy approved  
- [ ] Legacy `users.role` string retention period approved  

### Security
- [ ] Password algorithm: Argon2id (preferred) approved  
- [ ] CSRF required on all web auth POSTs  
- [ ] Session cookie flags for local vs production approved  
- [ ] Lockout thresholds approved  
- [ ] Rate limits for login/register/forgot approved  
- [ ] Email verification **required before apply/post** — **Yes / No**  
- [ ] Reset token TTL approved  
- [ ] Audit events list approved  

### Performance / scale
- [ ] Phase-1 rate-limit storage approved  
- [ ] Redis compatibility interfaces accepted as follow-up  
- [ ] Auth query patterns (no heavy joins) accepted  

### Documentation & process
- [ ] Docs locations in Section 5 accepted  
- [ ] Implementation order (Section 6) accepted  
- [ ] Test case outline ownership assigned  
- [ ] Rollback ownership assigned  

### Explicit non-goals for this phase
- [ ] Confirmed: do not break existing routes/pages  
- [ ] Confirmed: no auth implementation until this document is approved  
- [ ] Confirmed: OAuth/social login out of scope for foundation  

---

### Sign-off

| Role | Name | Date | Signature |
|---|---|---|---|
| Chief Software Architect | | | |
| Tech Lead | | | |
| Security Reviewer | | | |
| Product Owner | | | |

---

*End of authentication foundation pre-development review. No PHP, SQL, routes, or database files were modified by this document.*

# Authentication & Token Lifecycle v2

**Version:** `2.1.0` (Phase 2)  
**Branch:** `feature/auth-token-lifecycle-v2`  
**Migrations:** `065` (devices / refresh / MFA) · `066` (session access tokens)  
**Compatibility:** Additive — existing `/api/v1/tokens*` PAT APIs unchanged

---

## Phase 2: Access tokens ≠ PATs

| Token | Prefix (default) | Storage | TTL | Renew |
|---|---|---|---|---|
| **Access** (session) | `jva1_` | `auth_access_tokens` | **15 minutes** (`AUTH_ACCESS_TTL_SECONDS`, default `900`) | **Not renewable.** Obtain a new access token only via refresh rotation. |
| **Refresh** | `jvr1_` | `auth_refresh_tokens` | Long-lived (`AUTH_REFRESH_TTL_DAYS`, default 30) | Rotates on every `/auth/refresh`; previous refresh is immediately unusable |
| **PAT** | `jv1_` | `api_personal_access_tokens` | Existing day-based API TTL | Independent of access/refresh; not affected by access expiry |

### Middleware

`ApiBearerAuthenticator` (wired by `api.auth`):

1. Rejects refresh tokens presented as Bearer credentials.
2. Routes `jva1_…` to **AccessTokenService** (session validation + short expiry).
3. Routes other Bearer tokens (typically `jv1_…`) to **PersonalAccessTokenService** (PAT validation).
4. Sets `ApiAuth` with `kind: access|pat`.

Legacy Phase-1 session rows still stored as PATs named `access:*` remain authenticatable via the PAT path until they expire or are revoked.

### Logout semantics

- Device logout / session logout revokes **access** (+ refresh/device) — **not** developer PATs.
- `logout-everywhere` always revokes access+refresh+devices; PATs only when `revoke_pats` / config says so.
- `POST /tokens/revoke-all` revokes **PATs only** (skips legacy `access:*` names).

---

## Features

| Requirement | Implementation |
|---|---|
| Refresh token rotation | `RefreshTokenService::rotate` (transactional) |
| Refresh family tracking | `family_id` + reuse → revoke family |
| Device-based sessions | `auth_devices` + `DeviceSessionService` |
| Multi-device login | Multiple devices per user |
| Access / PAT separation | `auth_access_tokens` + `ApiBearerAuthenticator` |
| Email verification | `POST /api/v1/auth/email/*` |
| Password reset tokens | `POST /api/v1/auth/password/*` |
| PAT revocation | Existing revoke + `POST /tokens/revoke-all` |
| Logout everywhere | `POST /auth/logout-everywhere` |
| Account lockout | `AuthManager` / `login_attempts` |
| MFA-ready | `auth_mfa_factors` placeholders |
| Audit logging | `SecurityAuditLogger` |
| UTC expiry | `AuthTokenHasher` |
| APP_KEY hashing | HMAC-SHA256 pepper |
| OpenAPI | Lifecycle paths documented |

---

## Endpoints (additive)

Public: `/auth/status`, `/auth/login`, `/auth/refresh`, password + email flows  
Auth: `/auth/logout`, `/auth/logout-everywhere`, `/auth/devices`, `/auth/mfa*`, `/tokens/revoke-all`

---

## Config

| Key | Env | Default |
|---|---|---|
| `auth_lifecycle.access_ttl_seconds` | `AUTH_ACCESS_TTL_SECONDS` | `900` |
| `auth_lifecycle.access_prefix` | `AUTH_ACCESS_PREFIX` | `jva1_` |
| `auth_lifecycle.refresh_ttl_days` | `AUTH_REFRESH_TTL_DAYS` | `30` |
| `auth_lifecycle.refresh_prefix` | `AUTH_REFRESH_PREFIX` | `jvr1_` |
| `api.token_prefix` / TTL | `API_TOKEN_*` | PAT defaults unchanged |

---

## Verify

```bash
# Apply migration 066 if not yet applied
mysql … < database/migrations/066_create_auth_access_tokens_table.sql

composer auth-lifecycle-check
composer test -- --filter AuthLifecycle
```

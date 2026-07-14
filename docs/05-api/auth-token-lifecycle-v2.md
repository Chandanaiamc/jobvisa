# Authentication & Token Lifecycle v2

**Version:** `2.0.0`  
**Branch:** `feature/auth-token-lifecycle-v2`  
**Migration:** `065_create_auth_token_lifecycle_v2_tables.sql`  
**Compatibility:** Additive — existing `/api/v1/tokens*` PAT APIs unchanged

---

## Features

| Requirement | Implementation |
|---|---|
| Refresh token rotation | `RefreshTokenService::rotate` |
| Refresh family tracking | `family_id` + reuse → revoke family |
| Device-based sessions | `auth_devices` + `DeviceSessionService` |
| Multi-device login | Multiple devices per user |
| Email verification | `POST /api/v1/auth/email/*` → existing services |
| Password reset tokens | `POST /api/v1/auth/password/*` → existing services |
| PAT revocation | Existing revoke + `POST /tokens/revoke-all` |
| Logout everywhere | `POST /auth/logout-everywhere` |
| Account lockout | Reuses `AuthManager` / `login_attempts` (429 `account_locked`) |
| MFA-ready | `auth_mfa_factors` + status/register placeholders |
| Audit logging | `SecurityAuditLogger` on auth events |
| UTC expiry | `AuthTokenHasher` |
| APP_KEY hashing | HMAC-SHA256 pepper |
| Role-aware middleware | Existing seeker/employer gates preserved |
| OpenAPI | Lifecycle paths documented |

---

## Endpoints (additive)

Public: `/auth/status`, `/auth/login`, `/auth/refresh`, password + email flows  
Auth: `/auth/logout`, `/auth/logout-everywhere`, `/auth/devices`, `/auth/mfa*`, `/tokens/revoke-all`

---

## Verify

```bash
composer auth-lifecycle-check
composer api-check
composer test -- --testsuite Api
```

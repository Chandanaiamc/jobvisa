# Sprint 1 — Enterprise Authentication

**Status:** Backend complete (JSON endpoints — no UI pages)  
**Depends on:** Auth foundation, Security foundation, Route groups, migrations `027`–`031`

---

## Overview

Sprint 1 delivers the full **backend** authentication workflow:

| Capability | Implementation |
|---|---|
| Registration | `RegistrationService` + `POST /auth/register` |
| Login | `AuthManager::attempt` + throttling + `POST /auth/login` |
| Logout | `AuthManager::logout` + cookie clear + `POST /auth/logout` |
| Password reset request | `PasswordResetService::request` + `POST /auth/password/forgot` |
| Password reset confirm | `PasswordResetService::reset` + `POST /auth/password/reset` |
| Email verification | `EmailVerificationService` + verify/resend endpoints |
| Remember-me | `RememberMeService` + `RememberMeCookie` + middleware |
| Session regeneration | `Auth\SessionManager::establish/clear` |
| Login throttling | `AuthManager::isThrottled` via `login_attempts` |
| CSRF | `CsrfMiddleware` on mutating auth routes |
| Validation | `JobVisa\App\Security\Validator` |
| Role dashboards | `DashboardRedirector` + login/`/auth/redirect` |

---

## Related docs in this folder

- [Endpoints](./endpoints.md)
- [Middleware](./middleware.md)
- [Services](./services.md)
- [Testing](./testing.md)

---

## Architecture map

```text
routes/auth.php
    → MiddlewarePipeline (web, remember, guest|auth, csrf)
        → AuthController / PasswordController
            → RegistrationService / AuthManager / PasswordResetService / EmailVerificationService
                → UserRepository / PasswordHasher / LoginAttemptService / RememberMe*
```

Auth HTTP controllers live under `App\Controllers\Auth\` (legacy controller namespace).  
Domain services live under `JobVisa\App\Auth\`.

---

## Security notes

- Passwords hashed with Argon2id (bcrypt fallback).  
- Reset/verification tokens stored as **SHA-256 hashes only**.  
- Plain tokens returned in JSON **only until mailer is wired** (local/dev).  
- Session ID regenerated on login and logout.  
- CSRF required for POST auth endpoints (`_token` or `X-CSRF-TOKEN`).  
- Guest endpoints reject already-authenticated sessions.  

---

## Out of scope (this sprint)

- Frontend forms / Blade/PHP views for login UI  
- SMTP / email delivery provider  
- OAuth / social login  
- Fine-grained `permissions` table RBAC  

---

*See also: `docs/03-modules/authentication-foundation.md`*

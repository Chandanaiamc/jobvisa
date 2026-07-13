# Complete Authentication Flow (Sprint 2B)

**Scope:** Email verification UI, forgot/reset password UI, verified-email middleware, role-gated placeholders, mail/log fallback.  
**Constraints:** No migration changes, no destructive SQL, existing login/register retained, no full dashboards.

---

## Routes

### HTML

| Method | Path | Controller | Middleware |
|---|---|---|---|
| GET | `/email/verify` | `WebEmailController@notice` | web, remember |
| GET | `/email/verify/{token}` | `WebEmailController@verify` | web, remember |
| POST | `/email/verification-notification` | `WebEmailController@resend` | web, remember, csrf |
| GET | `/forgot-password` | `WebPasswordResetController@showForgot` | web, remember, csrf |
| POST | `/forgot-password` | `WebPasswordResetController@sendResetLink` | web, remember, csrf |
| GET | `/reset-password/{token}` | `WebPasswordResetController@showReset` | web, remember, csrf |
| POST | `/reset-password` | `WebPasswordResetController@reset` | web, remember, csrf |
| GET | `/register` | `WebAuthController@showRegister` | web, remember, guest.web, csrf |
| POST | `/register` | `WebAuthController@register` | web, remember, guest.web, csrf |
| GET | `/login` | `WebAuthController@showLogin` | web, remember, guest.web, csrf |
| POST | `/login` | `WebAuthController@login` | web, remember, guest.web, csrf |
| POST | `/logout` | `WebAuthController@logout` | web, remember, auth.web, csrf |
| GET | `/jobseeker` | `PortalController@jobseeker` | web, remember, auth.web, verified, jobseeker |
| GET | `/employer` | `PortalController@employer` | web, remember, auth.web, verified, employer |
| GET | `/admin` | `PortalController@admin` | web, remember, auth.web, verified, admin |

### JSON API (Sprint 1 — unchanged paths)

| Method | Path | Notes |
|---|---|---|
| POST | `/auth/password/forgot` | Generic success; **no** plain `reset_token` in response |
| POST | `/auth/password/reset` | Requires email, token, password, confirmation |
| POST | `/auth/email/verify` | Token body |
| POST | `/auth/email/resend` | Rate-limited |

---

## Controllers

| Class | Responsibility |
|---|---|
| `App\Controllers\Auth\WebEmailController` | Verify notice, link confirm, resend |
| `App\Controllers\Auth\WebPasswordResetController` | Forgot + reset HTML |
| `App\Controllers\Auth\WebAuthController` | Login/register/logout (Sprint 2A); redirects unverified users to `/email/verify` |
| `App\Controllers\Auth\PasswordController` | JSON password/email endpoints |
| `App\Controllers\PortalController` | Role placeholder dashboards |

---

## Services

| Service | Role |
|---|---|
| `EmailVerificationService` | Issue hashed tokens, verify once, expiry, rate-limited resend, `AuthMailer` |
| `PasswordResetService` | Generic forgot response, hashed tokens, expiry, single-use, rate limit, `PasswordHasher` |
| `RegistrationService` | Creates user + issues verification token |
| `AuthManager` | Login/logout, session, remember-me, brute-force via `LoginAttemptService` |
| `AuthMailer` | Local `storage/logs/mail-*.log` fallback; production never logs raw tokens |
| `RateLimiter` | Session-backed attempt buckets |

---

## Middleware mapping

| Alias | Class | Behaviour |
|---|---|---|
| `guest` | `GuestMiddleware` | JSON: block if already authenticated |
| `guest.web` | `RedirectIfAuthenticatedMiddleware` | HTML: redirect authenticated users away from login/register |
| `auth` / `AuthMiddleware` | `AuthenticateMiddleware` / `AuthMiddleware` | Require session (JSON) |
| `auth.web` | `RequireAuthWebMiddleware` | Require session (HTML → login) |
| `verified` | `VerifiedEmailMiddleware` | Require `email_verified_at`; else `/email/verify` |
| `admin` | `RoleMiddleware` | Roles: `admin`, `super_admin`, `staff` |
| `employer` | `RoleMiddleware` | Role: `employer` |
| `jobseeker` | `RoleMiddleware` | Role: `seeker` |
| `csrf` | `CsrfMiddleware` | CSRF on mutating requests |
| `remember` | `RememberMeMiddleware` | Restore session from cookie |
| `web` | `StartSessionMiddleware` | Start session |

### Access rules

- Guests: login, register, forgot/reset password, public pages.
- Authenticated: blocked from login/register (`guest.web`).
- Unverified: can use verification pages + logout; dashboards require `verified`.
- Wrong role on portal → HTTP 403 (`app/views/errors/403.php`).

### Role redirects (`DashboardRedirector` / `config/auth.php`)

| Role slug | Path |
|---|---|
| `seeker` | `/jobseeker` |
| `employer` | `/employer` |
| `admin`, `super_admin`, `staff` | `/admin` |

---

## Token lifecycle

### Email verification

1. Registration calls `EmailVerificationService::issue($userId)`.
2. Cryptographically secure plain token generated; **only SHA-256 hash** stored in `email_verification_tokens`.
3. Prior unused tokens for the user are marked used (`verified_at`).
4. Expiry from `auth.email_verification.expire_hours` (default 48h).
5. Mail/log contains link `GET /email/verify/{token}` (link only in non-production logs/context).
6. On success: row `verified_at` set, `users.email_verified_at` set; token cannot be reused.
7. Resend: `POST /email/verification-notification` — rate-limited; generic success message.

### Password reset

1. `POST /forgot-password` accepts email; **always** returns the same generic message when valid.
2. If account exists: invalidate prior unused tokens, insert new hash, expire per `auth.password_reset.expire_minutes`.
3. Mail/log link: `GET /reset-password/{token}`.
4. Reset form requires password + confirmation; `PasswordHasher` updates hash.
5. Token marked `used_at`; `users.remember_token` cleared; session regenerated / logout if signed in.
6. Redirect to `/login` with success flash.

---

## Security controls

| Control | Implementation |
|---|---|
| CSRF | `csrf_field()` + `CsrfMiddleware` on POSTs |
| Output escaping | `e()` in views |
| Generic auth errors | Login + forgot-password messaging |
| Session regeneration | Login (`AuthSessionManager`), logout, password reset |
| Prepared statements | `Database::query` bindings |
| Password hashing | `PasswordHasher` (argon2id preference) |
| Token hashing | SHA-256 of plain token at rest |
| Expiring tokens | DB `expires_at` checks |
| Brute-force | `LoginAttemptService` |
| Rate limiting | `RateLimiter` on resend + forgot |
| POST-only logout | No `GET /logout` |
| Secrets in logs | Production strips token context; no passwords logged |

---

## Views

| Path | Purpose |
|---|---|
| `app/views/auth/verify-email.php` + `-form.php` | Verify notice / resend |
| `app/views/auth/forgot-password.php` + `-form.php` | Forgot password |
| `app/views/auth/reset-password.php` + `-form.php` | Reset password |
| `app/views/errors/403.php` | Forbidden |
| `app/views/portal/placeholder.php` | Name, role, verification status, logout |

Uses existing auth layout, flash partials, and `public/assets/css/auth.css`.

---

## Testing instructions

### Automated

```bash
E:\localhost\php\php.exe vendor/bin/phpunit --filter AuthWorkflowTest
```

### Manual checklist

1. Register a seeker → redirected to `/email/verify`; mail entry in `storage/logs/mail-YYYY-MM-DD.log`.
2. Open verification link → `email_verified_at` set; redirect to `/jobseeker`.
3. Resend verification while unverified → success flash; spam resend → throttle message.
4. Login unverified → redirected to `/email/verify`; cannot open `/jobseeker`.
5. Forgot password with known email → generic success; link in mail log.
6. Forgot password with unknown email → same generic success.
7. Reset with valid token → login works with new password; old remember-me invalid.
8. Reset with reused/expired token → error; form not usable.
9. Employer on `/jobseeker` → 403 page.
10. Admin on `/admin` → placeholder with name/role/verified + POST logout.
11. CSRF missing on POST → rejected.
12. Health + public home still load.

---

## Final verification status

See Sprint 2B verification report in the agent response after read-only checks.

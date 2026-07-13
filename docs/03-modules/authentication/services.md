# Authentication Services

| Service | Responsibility |
|---|---|
| `AuthManager` | Login attempt, throttling, session login/logout, user accessors |
| `RegistrationService` | Validate + create user + issue email verification |
| `PasswordResetService` | Forgot / reset using `password_reset_tokens` |
| `EmailVerificationService` | Issue / verify / resend using `email_verification_tokens` |
| `RememberMeService` | Hash/store/validate remember tokens on `users.remember_token` |
| `RememberMeCookie` | HTTP cookie queue/forget/restore |
| `DashboardRedirector` | Map role → dashboard path from `config/auth.php` |
| `LoginAttemptService` | Persist/count failures for throttling |
| `PasswordHasher` | Argon2id / bcrypt |
| `UserRepository` (Auth) | Auth-specific user reads/writes (not enterprise `Repositories\UserRepository`) |
| `Auth\SessionManager` | Regenerate session ID; set/clear auth keys |

## Throttling

Configured in `config/auth.php`:

- `login_attempts.window_minutes` (default 15)
- `login_attempts.max_failures` (default 5)

`AuthManager::attempt()` refuses with `throttled=true` when email or IP failure counts exceed the limit.

## Dashboard map

```php
'admin' => '/admin/dashboard',
'employer' => '/employer/dashboard',
'seeker' => '/jobseeker/dashboard',
'staff' => '/admin/dashboard',
'default' => '/',
```

Dashboard **paths** are returned for clients; HTML dashboard pages are not part of Sprint 1.

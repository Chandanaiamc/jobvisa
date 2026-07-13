# Authentication Foundation

**Module:** Enterprise authentication services (no UI / controllers / routes)  
**Status:** Foundation ready for future login & registration HTTP layer  

---

## Components

| Service | Class | Role |
|---|---|---|
| Password hasher | `JobVisa\App\Auth\PasswordHasher` | Argon2id with bcrypt fallback; verify + rehash |
| Auth session | `JobVisa\App\Auth\SessionManager` | Auth identity keys + session ID regeneration |
| HTTP session | `JobVisa\App\Security\SessionManager` | Underlying secure PHP session (unchanged) |
| User repository | `JobVisa\App\Auth\UserRepository` | Active user lookup / password & remember updates |
| Login attempts | `JobVisa\App\Auth\LoginAttemptService` | Writes/reads `login_attempts` |
| Remember-me | `JobVisa\App\Auth\RememberMeService` | Issue/hash/store/validate/clear `users.remember_token` |
| Orchestrator | `JobVisa\App\Auth\AuthManager` | `attempt`, `loginUser`, `logout`, `check`, `user` |
| Provider | `JobVisa\App\Providers\AuthServiceProvider` | DI registration |

---

## Authentication flow

```text
AuthManager::attempt(email, password, remember?)
  │
  ├─ normalize email
  ├─ UserRepository::findActiveByEmail (deleted_at IS NULL, status ≠ deleted)
  ├─ on miss → LoginAttemptService::record(false) → fail
  ├─ PasswordHasher::verify
  ├─ on fail → record(false) → fail
  ├─ PasswordHasher::needsRehash? → update password_hash
  ├─ Auth SessionManager::establish (regenerate session ID)
  ├─ touch last_login_at
  ├─ LoginAttemptService::record(true)
  └─ remember? → RememberMeService::issue → returns plain+hash
                 (cookie set deferred to future HTTP layer)
```

Logout:

```text
AuthManager::logout()
  ├─ RememberMeService::clear(userId)
  └─ Auth SessionManager::clear (drop auth keys + regenerate ID)
```

---

## Session lifecycle

1. `SessionServiceProvider` starts HTTP session (secure cookie flags).  
2. On successful auth, `Auth\SessionManager::establish()`:
   - `session_regenerate_id(true)` via HTTP SessionManager  
   - sets `auth.user_id`, optional `auth.role_id`, `auth.login_at`  
3. On logout, auth keys removed and session ID regenerated again.  
4. Future “remember me” cookie will call `RememberMeService::validate()` then `establish()`.

---

## Password hashing strategy

| Preference | Condition |
|---|---|
| **Argon2id** | `PASSWORD_ARGON2ID` available (PHP 8.2+) |
| **bcrypt** | Fallback (`PASSWORD_BCRYPT`, cost 12) |

- Store only `password_hash` output in `users.password_hash`.  
- On successful login, transparently rehash if algorithm/options changed.  
- Never log raw passwords (Logger redacts password-like context keys).

---

## Security considerations

- Prepared statements only (`Database::query`).  
- Timing-safe remember-token compare (`hash_equals`).  
- Remember tokens stored as **SHA-256 hashes**, not plaintext.  
- Session fixation mitigated by regenerate-on-login/logout.  
- Login attempts capture email, IP, user-agent, success flag (schema `031`).  
- Soft-deleted users (`deleted_at` / `status=deleted`) cannot authenticate.  
- No HTML forms or public routes added — reduces attack surface until HTTP layer lands.  
- `AuthManager::attempt` returns a structured array; HTTP layer must not echo secrets.

---

## Future login / registration integration

1. Add `AuthController` + views (CSRF-protected forms).  
2. Routes: `/login`, `/register`, `/logout` (additive only).  
3. On login success with remember: set HttpOnly cookie from `remember.plain`; never store plain in DB.  
4. Middleware: `AuthMiddleware` / `GuestMiddleware` using `AuthManager::check()`.  
5. Registration: `PasswordHasher::hash` + insert user + profile; optional email verification tokens (`030`).  
6. Lockout: use `LoginAttemptService::countRecentFailuresByEmail/Ip` with `config/auth.php` thresholds.  
7. Password reset: separate service on `password_reset_tokens` (`029`).

---

## Container usage (today)

```php
/** @var \JobVisa\App\Auth\AuthManager $auth */
$auth = container(\JobVisa\App\Auth\AuthManager::class);

$result = $auth->attempt('user@example.com', 'secret', remember: false);

if ($result['success']) {
    // $auth->check() === true
}

$auth->logout();
```

Do not call this from public pages until controllers exist and migrations `027`–`031` are applied.

---

*End of authentication foundation documentation.*

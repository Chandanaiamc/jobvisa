# Security Foundation Implementation

**Project:** JobVisa.lk Enterprise  
**Scope:** Pre-authentication security primitives only  
**Status:** Implemented (no login/register/RBAC yet)

---

## Created classes

| Class | Path | Purpose |
|---|---|---|
| `JobVisa\App\Security\SessionManager` | `app/Security/SessionManager.php` | Secure session start, regenerate, get/set/remove/destroy, flash |
| `JobVisa\App\Security\Csrf` | `app/Security/Csrf.php` | CSRF token generate, store, validate, rotate, HTML field |
| `JobVisa\App\Security\Validator` | `app/Security/Validator.php` | Structured input validation without DB |
| `JobVisa\App\Security\SecurityHelper` | `app/Security/SecurityHelper.php` | Escape, random tokens, IP, user-agent |
| `JobVisa\App\Foundation\ExceptionHandler` | `app/Foundation/ExceptionHandler.php` | Dev vs production errors + logging |
| `JobVisa\App\Logging\Logger` | `app/Logging/Logger.php` | File logger (`info`, `warning`, `error`, `security`) |

Supporting files:

- `config/session.php` — session options from environment  
- `app/views/errors/500.php` — safe production error page  
- Helpers: `csrf_token()`, `csrf_field()`, `security_escape()`  
- `.env.example` — `SESSION_*` keys  
- `bootstrap/autoload.php` — `JobVisa\App\` / `JobVisa\Core\` fallback PSR-4  
- `bootstrap/app.php` — registers ExceptionHandler + starts session  

---

## Security purpose

| Component | Why it exists before auth |
|---|---|
| SessionManager | Safe cookie flags, fixation-ready regenerate, flash for form UX |
| CSRF | Required before any state-changing auth forms |
| Validator | Consistent server-side validation for future auth inputs |
| SecurityHelper | Shared primitives for tokens, escaping, request meta |
| ExceptionHandler | Prevent stack traces in production; log safely |
| Logger | Security/audit-friendly file trail without secret leakage |

---

## Usage examples

### Session

```php
use JobVisa\App\Security\SessionManager;

SessionManager::start();
SessionManager::set('demo', 'value');
$value = SessionManager::get('demo');
SessionManager::flash('status', 'Saved');
$message = SessionManager::getFlash('status');
SessionManager::regenerate();
SessionManager::destroy();
```

### CSRF (forms)

```php
<form method="post" action="/future-endpoint">
    <?= csrf_field() ?>
    <!-- fields -->
</form>
```

```php
use JobVisa\App\Security\Csrf;

if (!Csrf::validateAndRotate($_POST['_token'] ?? null)) {
    http_response_code(419);
    exit('Invalid CSRF token');
}
```

### Validator

```php
use JobVisa\App\Security\Validator;

$validator = Validator::make($_POST)
    ->required('email')
    ->email('email')
    ->required('password')
    ->min('password', 8)
    ->max('password', 72)
    ->confirmed('password')
    ->in('account_type', ['seeker', 'employer']);

if ($validator->fails()) {
    $errors = $validator->errors(); // ['email' => ['...'], ...]
}
```

### Security helper

```php
use JobVisa\App\Security\SecurityHelper;

$html = SecurityHelper::escape($userInput);
$token = SecurityHelper::randomToken(32);
$ip = SecurityHelper::clientIp();
$ua = SecurityHelper::userAgent();
```

### Logger

```php
use JobVisa\App\Logging\Logger;

Logger::info('Security foundation bootstrapped');
Logger::security('CSRF validation failed', ['ip' => SecurityHelper::clientIp()]);
Logger::error('Unhandled condition', ['password' => 'secret']); // stored as [REDACTED]
```

---

## Known limitations

1. **No middleware pipeline yet** — CSRF must be called manually in future controllers.  
2. **Session storage is PHP files** — not Redis; not multi-node safe by default.  
3. **Client IP** may be spoofable via `X-Forwarded-For` unless the reverse proxy is trusted/configured.  
4. **Logger** is append-only files under `storage/logs`; no rotation daemon included.  
5. **Validator** does not check DB uniqueness (by design — no queries in this phase).  
6. **ExceptionHandler** HTML is minimal; JSON API error envelopes are not implemented yet.  
7. **Session starts on every web bootstrap** — expected for CSRF readiness; CLI scripts that boot the app will also start a session if headers allow.

---

## Future migration path

### Redis sessions / rate limits

- Introduce `SessionStoreInterface` with `FileSessionStore` (current) and `RedisSessionStore`.  
- Keep `SessionManager` as the façade so auth code does not change.

### Monolog

- Replace `Logger` internals with Monolog handlers (rotating file, stderr, syslog).  
- Preserve method names (`info`, `warning`, `error`, `security`) via a thin adapter.  
- Keep redaction middleware/processor for passwords, tokens, and session identifiers.

---

## Explicitly out of scope (this delivery)

- Login / registration / password reset / email verification  
- RBAC middleware  
- Database queries  
- Changes to public routes or existing page controllers/views (except new `errors/500.php`)  
- Composer install / SQL execution  

---

*End of security foundation implementation notes.*

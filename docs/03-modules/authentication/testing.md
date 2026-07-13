# Authentication Testing

## PHPUnit

```bash
composer test
# or
vendor/bin/phpunit
```

Config: `phpunit.xml`  
Bootstrap: `tests/bootstrap.php`

### Suites

| Test | Type | Coverage |
|---|---|---|
| `tests/Unit/Auth/PasswordHasherTest.php` | Unit | Hash/verify |
| `tests/Unit/Auth/DashboardRedirectorTest.php` | Unit | Role redirects |
| `tests/Unit/Security/AuthValidationTest.php` | Unit | Register validation rules |
| `tests/Unit/Http/CsrfMiddlewareTest.php` | Unit | CSRF middleware |
| `tests/Feature/Auth/AuthWorkflowTest.php` | Feature | Register → verify → login → reset → logout (requires DB) |

Feature tests skip automatically when the database is unavailable.

## Manual API smoke

1. `GET /auth/csrf` → capture `csrf_token`  
2. `POST /auth/register` with `_token`  
3. `POST /auth/email/verify` with returned `verification_token`  
4. `POST /auth/login`  
5. `GET /auth/me`  
6. `POST /auth/logout` with CSRF  

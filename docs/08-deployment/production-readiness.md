# Production Readiness (Sprint 4.1)

**Project:** JobVisa.lk Enterprise  
**Rules version:** `4.1.0`  
**Status:** Implemented

Hardens the platform for staging/production without changing jobseeker/employer business features.

---

## Capabilities

- Security response headers (CSP, frame options, nosniff, referrer, permissions)
- Optional HSTS (`HSTS_ENABLED`)
- Optional force HTTPS (`FORCE_HTTPS`)
- Maintenance mode (`APP_MAINTENANCE`) with IP / secret bypass
- JSON ops probes: `/health`, `/health/live`, `/health/ready`
- Production environment guard (debug off, insecure DB credentials, seed password warnings)
- `public/robots.txt`
- CLI checker: `php scripts/production-check.php`

---

## Environment flags

| Variable | Purpose | Production recommendation |
|---|---|---|
| `APP_ENV` | `local` / `staging` / `production` | `production` |
| `APP_DEBUG` | Verbose errors | `false` |
| `FORCE_HTTPS` | Redirect HTTP→HTTPS | `true` |
| `SESSION_SECURE` | Secure cookies | `true` (with HTTPS) |
| `SECURITY_HEADERS_ENABLED` | Send security headers | `true` |
| `HSTS_ENABLED` | Strict-Transport-Security | `true` (after HTTPS verified) |
| `APP_MAINTENANCE` | 503 maintenance page | `false` normally |
| `MAINTENANCE_SECRET` | Bypass via `?maintenance_secret=` or `X-Maintenance-Secret` | strong secret |
| `MAINTENANCE_ALLOW_IP` | Comma-separated bypass IPs | ops IPs |
| `FAIL_ON_INSECURE_PRODUCTION` | Hard-fail boot on critical prod issues | `true` |

---

## Health probes

| Path | Meaning | Auth |
|---|---|---|
| `GET /health/live` | Process is up | none |
| `GET /health/ready` | DB + storage + prod guard | none |
| `GET /health` | Combined summary | none |
| `GET /health/database` | HTML DB diagnostic | session (legacy) |
| `GET /health/container` | DI diagnostic (local/debug only) | session |

Ready returns **503** when database is down or production guard reports critical issues.

---

## Verification

```bash
E:\localhost\php\php.exe scripts/production-check.php
```

Expect final line: `PASS`

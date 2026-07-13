# Hosting Deployment

**Project:** JobVisa.lk Enterprise  
**Related:** `docs/08-deployment/production-readiness.md`, `environment-configuration.md`, `backup-and-recovery.md`, `deployment-automation.md`

---

## Document root

Point the web server document root to **`public/`** only. Never expose `app/`, `config/`, `storage/`, `database/`, or `.env`.

Example (Apache alias / vhost):

- DocumentRoot → `/var/www/jobvisa/public`
- `AllowOverride All` so `public/.htaccess` rewrite rules apply

---

## Release checklist

1. Deploy a known Git revision / release tag.
2. Copy `.env.example` → `.env` and set production values (never commit `.env`).
3. Set at minimum:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL=https://your-domain`
   - `FORCE_HTTPS=true`
   - `SESSION_SECURE=true`
   - `SECURITY_HEADERS_ENABLED=true`
   - Strong `DB_*` credentials (not root/empty)
4. Run pending SQL migrations in order (backup first).
5. Ensure `storage/logs` and `storage/uploads` are writable by the PHP user.
6. Smoke-check:
   - `GET /health/live` → 200 JSON
   - `GET /health/ready` → 200 JSON
   - Login (seeker + employer)
   - One resume AI page (e.g. intelligence)
7. Run `php scripts/production-check.php` in the release environment (or CI).

---

## Maintenance window

```env
APP_MAINTENANCE=true
MAINTENANCE_SECRET=long-random-secret
MAINTENANCE_ALLOW_IP=203.0.113.10
```

Ops can bypass with `?maintenance_secret=...` or header `X-Maintenance-Secret`. Health live/ready remain reachable for LB probes.

---

## Rollback

1. Keep previous release artifact/version.
2. Restore DB backup if a migration is unsafe to reverse.
3. Re-point document root / symlink to previous release.
4. Confirm `/health/ready` returns 200.

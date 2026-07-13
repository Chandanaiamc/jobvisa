# Environment Configuration

**Project:** JobVisa.lk Enterprise  
**Source of truth for keys:** `.env.example`

---

## Environments

| APP_ENV | APP_DEBUG | Notes |
|---|---|---|
| `local` | `true` | Developer machine; seeders allowed |
| `staging` | `false` | Prod-like; guard warnings logged |
| `production` | `false` | Hard-fail on critical guard findings when enabled |

---

## Core application

| Key | Description |
|---|---|
| `APP_NAME` | Product display name |
| `APP_URL` | Public base URL (include path if app lives in a subdirectory) |
| `APP_TIMEZONE` | Default `Asia/Colombo` |

## Database

| Key | Description |
|---|---|
| `DB_HOST` / `DB_PORT` / `DB_NAME` / `DB_USER` / `DB_PASSWORD` | PDO connection |

## Session

| Key | Description |
|---|---|
| `SESSION_NAME` | Cookie name |
| `SESSION_LIFETIME` | Minutes |
| `SESSION_SECURE` | Require HTTPS cookies |
| `SESSION_HTTP_ONLY` | Block JS access to session cookie |
| `SESSION_SAME_SITE` | `Lax` / `Strict` / `None` |

## Performance readiness (Sprint 4.2)

See `performance-optimization.md` for `CACHE_*`, `QUERY_PROFILE`, timing headers, and pagination clamps.

## Monitoring & observability (Sprint 4.3)

See `monitoring-observability.md` for `OBS_*` request IDs, access logs, metrics, error ring buffer, and alert webhook.

## Deployment automation (Sprint 4.4)

See `deployment-automation.md` for `DEPLOY_*`, mysqldump paths, confirmation tokens, and the deploy CLI.

## Enterprise API (Sprint 4.5)

See `docs/05-api/enterprise-api-platform.md` for `API_*` tokens, rate limits, CORS, and webhooks.

## Developer portal & SDK (Sprint 4.6)

See `docs/05-api/developer-portal-and-sdk.md` for `DEV_PORTAL_*` and the PHP SDK foundation.

## Security hardening (Sprint 4.7)

See `docs/02-system-design/enterprise-security-hardening.md` for password policy, CSP, audit, and OWASP mapping.

## Seeders (local/demo only)

`SEED_*` credentials must be changed before any shared environment. Production guard warns on default `ChangeMe*` passwords.

---

## Loading order

1. `bootstrap/app.php` loads `.env`
2. `config/*.php` files resolve via `env()`
3. Service providers boot (including `ProductionServiceProvider` audit)

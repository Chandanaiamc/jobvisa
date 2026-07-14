# Enterprise API v1 — Production Hardening

**Status:** Implemented (Phase A + B + C)  
**Base platform:** Sprint 4.5 (`ApiVersion::CURRENT = 4.5.0`)  
**Migration:** None

---

## Hardening summary

| Area | Change |
|---|---|
| Rate limit | Request-scoped memo via `ApiRateLimiter::beginRequest()` (FPM-safe) |
| Rate limit store | Fail-closed on I/O error → HTTP 503 `rate_limit_unavailable` |
| PAT pepper | Non-empty `APP_KEY` required outside local/testing/development |
| Token expiry | Store + compare as **UTC** |
| CORS | Reflect-any-Origin only in local-like envs when allow-list empty |
| Trusted proxies | Empty `TRUSTED_PROXIES` only trusts XFF in local-like envs |
| OpenAPI | Documents revoke + `/docs/openapi`; docs endpoint serves **raw** JSON by default (`?envelope=1` optional) |
| Role gates | Resume/match routes require `api.jobseeker` |
| Error leakage | API 500 messages only when `APP_DEBUG` **and** local-like env |
| Logging | Redacts `Bearer …` and `jv1_…` substrings |
| Webhooks | Remain **disabled** by default (`API_WEBHOOKS_ENABLED=false`) |

---

## Production checklist

| Variable | Required | Notes |
|---|---|---|
| `APP_KEY` | **Yes** | Non-empty; used as PAT HMAC pepper |
| `APP_DEBUG` | `false` | Prevents internal error leakage |
| `APP_ENV` | `production` / `staging` | Disables local CORS / XFF shortcuts |
| `API_CORS_ORIGINS` | Comma allow-list | Required for browser API clients |
| `TRUSTED_PROXIES` | Proxy IPs | Required behind reverse proxies for accurate RL/audit IPs |
| `FORCE_HTTPS` | `true` | Production TLS termination |
| `API_WEBHOOKS_ENABLED` | `false` | Keep off until SSRF hardening |

After changing `APP_KEY`, existing PATs must be **re-issued** (hashes will not match).

---

## Verify

```bash
composer api-check
composer test -- --testsuite Api,Security
```

Expect `api-check` final line: `PASS`.

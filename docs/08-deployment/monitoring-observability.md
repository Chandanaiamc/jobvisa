# Monitoring & Observability (Sprint 4.3)

**Project:** JobVisa.lk Enterprise  
**Rules version:** `4.3.0`  
**Status:** Implemented

Adds request correlation, access logging, file metrics, recent-error tracking, and optional alert webhooks without changing product business rules.

---

## Capabilities

- Request ID middleware (`X-Request-Id` echo / accept)
- Structured access logs via `Logger` (sampled)
- Daily file metrics under `storage/metrics/`
- Recent error ring buffer for ops triage
- Optional webhook alerts for 5xx / tracked errors
- Ops probes: `GET /health/observability`, `GET /metrics`
- CLI: `php scripts/observability-check.php`

---

## Environment flags

| Variable | Default | Purpose |
|---|---|---|
| `OBS_ENABLED` | `true` | Master switch |
| `OBS_REQUEST_ID_HEADER` | `X-Request-Id` | Correlation header name |
| `OBS_ACCESS_LOG` | `true` | Emit `http_access` log lines |
| `OBS_ACCESS_LOG_SAMPLE` | `100` | Percent of requests to access-log (1–100) |
| `OBS_METRICS_ENABLED` | `true` | Persist counters / latency |
| `OBS_METRICS_SECRET` | _(empty)_ | Required in staging/production for `/metrics` |
| `OBS_ERROR_TRACKING` | `true` | Ring-buffer recent errors |
| `OBS_ERROR_RING_SIZE` | `50` | Max recent errors retained |
| `OBS_ALERT_WEBHOOK_URL` | _(empty)_ | Optional POST JSON webhook |
| `OBS_ALERT_ON_5XX` | `false` | Fire webhook on HTTP 5xx |

---

## Ops endpoints

| Path | Notes |
|---|---|
| `/health/observability` | JSON status (logging writable, metrics snapshot, recent errors) |
| `/metrics` | Counters + latency; open in `local`, secret-gated in staging/production |

Skipped from access metrics (noise): `/health/live`, `/health/ready`, `/metrics`.

---

## Production guidance

1. Set a strong `OBS_METRICS_SECRET` before staging/production.
2. Keep `OBS_ALERT_ON_5XX=false` until a webhook endpoint is ready.
3. Ensure `storage/logs` and `storage/metrics` are writable by PHP and denied by web server (`.htaccess` written on boot).
4. Correlate incidents with `X-Request-Id` from response headers and app logs.
5. Verify `GET /health/observability` after deploy.

---

## Verification

```bash
E:\localhost\php\php.exe scripts/observability-check.php
```

Expect final line: `PASS`

# Performance & Optimization (Sprint 4.2)

**Project:** JobVisa.lk Enterprise  
**Rules version:** `4.2.0`  
**Status:** Implemented

Adds measurable performance tooling and safe caching without changing product business rules.

---

## Capabilities

- File cache (`storage/cache`) behind `CacheInterface` (Redis-ready driver hook)
- Catalog caching for skills, languages, countries, country cities
- Query profiler (optional) with slow-query logging and per-request budgets
- Request timing middleware (`X-Response-Time`, `X-Query-Count`, `Server-Timing`)
- Shared `Paginator` helper for consistent page/per-page clamps
- Static asset Expires + Deflate in `public/.htaccess`
- Ops probe: `GET /health/performance`
- CLI: `php scripts/performance-check.php`

---

## Environment flags

| Variable | Default | Purpose |
|---|---|---|
| `CACHE_ENABLED` | `true` | Toggle file cache |
| `CACHE_DRIVER` | `file` | `file` now; Redis later |
| `CATALOG_CACHE_TTL` | `3600` | Seconds for reference catalogs |
| `QUERY_PROFILE` | `false` | Enable SQL timing (staging/debug) |
| `SLOW_QUERY_MS` | `100` | Slow query threshold |
| `QUERY_BUDGET` | `40` | Soft per-request query count budget |
| `RESPONSE_TIMING` | `true` | Emit timing headers |
| `SLOW_REQUEST_MS` | `800` | Log slow HTTP requests |
| `DEFAULT_PER_PAGE` / `MAX_PER_PAGE` | `15` / `50` | Pagination clamps |

---

## Production guidance

1. Keep `QUERY_PROFILE=false` in production unless diagnosing.
2. Enable OPcache on the PHP host (`opcache.enable=1`).
3. Run `composer dump-autoload -o` on deploy.
4. Do not cache personalized resume/AI payloads in shared cache.
5. Verify `GET /health/performance` after deploy.

---

## Verification

```bash
E:\localhost\php\php.exe scripts/performance-check.php
```

Expect final line: `PASS`

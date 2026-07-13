# JobVisa.lk Enterprise — Release Notes

**Version:** 1.0.0 (`v1.0.0`)  
**Release date:** 2026-07-13  
**Vendor:** Readleaf (Pvt) Ltd  
**Status:** Enterprise General Availability (GA)

---

## Summary

JobVisa.lk **1.0.0** is the first enterprise production release of the Sri Lanka–focused overseas employment platform. It packages authenticated job-seeker and employer experiences, AI-assisted career tools, a versioned public API with developer portal, and full operational readiness (production health, performance, observability, deployment, security, accessibility, and QA gates).

---

## What’s included

| Area | Highlights |
|---|---|
| Product | Job seeker dashboards, employer AI assistants, marketplace foundations |
| AI modules | Matching, ranking, resume/cover letter tooling, salary & skill insights, interviews |
| API | `/api/v1` JSON platform, personal access tokens, OpenAPI, PHP SDK |
| Ops | Live/ready probes, caching, metrics, deploy/backup/rollback tooling |
| Security | CSP, password policy, audit logging, CSRF, trusted proxy controls |
| Quality | PHPUnit suites + Release Candidate + Enterprise Release verification |

---

## Requirements

- PHP **≥ 8.2** (extensions: `pdo_mysql`, `json`, `mbstring`; `zip` recommended for Composer)
- MySQL compatible database with migrations applied
- Writable `storage/` (logs, cache, uploads, releases)
- Configured `.env` (`APP_KEY`, database, `APP_URL`)

---

## Verification

Before promoting this build:

```bash
composer release-candidate-check
composer enterprise-release-check
```

Both must print **`PASS`**.

Health probes (configured environment):

- `GET /health/live`
- `GET /health/ready`

---

## Upgrade / install notes

1. Deploy code tagged `v1.0.0`.
2. Install Composer dependencies (`composer install --no-dev` in production).
3. Run pending migrations via approved ops process (`scripts/deploy.php` or migration runner).
4. Confirm `VERSION` reads `v1.0.0` and `release/manifest.json` matches.
5. Run `composer enterprise-release-check` on the target environment (or staging mirror).

---

## Known limitations

- Browser E2E (Playwright/Cypress) is not part of the 1.0.0 gate.
- Full WCAG audit of every screen is foundation-level (Sprint 4.8), not exhaustive.
- Deployment gate remains opt-in inside RC (`TESTING_RC_DEPLOYMENT_GATE`) due to stamp/maintenance side effects.

---

## Support

Copyright © 2026 Readleaf (Pvt) Ltd. All rights reserved.  
See `LICENSE` for licensing terms.

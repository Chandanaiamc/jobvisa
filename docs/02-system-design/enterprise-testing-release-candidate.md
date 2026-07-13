# Enterprise Testing, QA Automation & Release Candidate (Sprint 4.9)

**Project:** JobVisa.lk Enterprise  
**Rules version:** `4.9.0`  
**Status:** Implemented  
**Migration:** None

---

## Goal

Provide a Release Candidate (RC) quality gate that aggregates unit, feature, integration, API, security, performance, and smoke coverage — plus prior enterprise CLI checks (4.1–4.8) — without breaking existing modules.

---

## Deliverables

| Component | Role |
|---|---|
| `ReleaseCandidateService` | Checklist evaluation + RC status |
| `QaGateRunner` | Runs enterprise `*-check.php` scripts + PHPUnit |
| `SmokeTestService` | In-process production/security/API/deploy smoke |
| `RegressionSuiteService` | Category summary for reporting |
| `config/testing.php` | Gate / PHPUnit flags |
| `scripts/release-candidate-check.php` | Single RC CLI → `PASS` / `FAIL` |
| PHPUnit suites | Unit, Feature, Integration, Api, Security, Performance, Smoke |
| Docs | This file + `docs/07-testing/release-candidate-checklist.md` |

---

## Test suites

| Suite | Path | Intent |
|---|---|---|
| Unit | `tests/Unit` | Fast pure-logic regression |
| Feature | `tests/Feature` | Auth workflow (DB) |
| Integration | `tests/Integration` | DI platform + RC checklist |
| Api | `tests/Api` | OpenAPI, routes, JSON envelope |
| Security | `tests/Security` | Password policy, XSS escape, CSRF |
| Performance | `tests/Performance` | Cache round-trip + health |
| Smoke | `tests/Smoke` | In-process enterprise smoke |

---

## Enterprise gate orchestration

Default gates (when `TESTING_RC_GATES=true`):

1. production-check (4.1)  
2. performance-check (4.2)  
3. observability-check (4.3)  
4. api-check (4.5)  
5. api-portal-check (4.6)  
6. security-check (4.7)  
7. frontend-check (4.8)  

`deployment-check` (4.4) is **opt-in** via `TESTING_RC_DEPLOYMENT_GATE=true` because it stamps releases / toggles maintenance. RC still validates `DeploymentManager` dry-run in smoke.

---

## Environment flags

| Variable | Default | Purpose |
|---|---|---|
| `TESTING_RC_ENABLED` | `true` | Master switch |
| `TESTING_RC_PHPUNIT` | `true` | Run PHPUnit inside RC check |
| `TESTING_RC_GATES` | `true` | Run prior enterprise CLIs |
| `TESTING_RC_DEPLOYMENT_GATE` | `false` | Include deployment-check |

---

## Verify

```bash
composer release-candidate-check
# or: php scripts/release-candidate-check.php
```

Expect final line: `PASS`.

Also:

```bash
composer test
composer test:unit
```

---

## Compatibility

Prior version constants remain unchanged:

`4.1.0` … `4.8.0` (production → frontend). Sprint 4.9 only adds the Testing / RC layer.

---

## Out of scope

- Browser E2E (Playwright/Cypress)
- Changing product modules or schemas
- Forcing deployment-check side effects in default RC

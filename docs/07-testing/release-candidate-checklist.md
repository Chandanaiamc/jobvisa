# Release Candidate Checklist (Sprint 4.9)

Use this checklist before promoting a build past Release Candidate.

## Meta

- [ ] Rules version `4.9.0` (`ReleaseCandidateVersion::CURRENT`)
- [ ] `composer release-candidate-check` prints `PASS`
- [ ] No intentional breaking changes to modules 4.1–4.8

## Automated suites

- [ ] Unit suite green
- [ ] Feature suite green (DB available)
- [ ] Integration suite green
- [ ] API suite green
- [ ] Security regression green
- [ ] Performance regression green
- [ ] Smoke suite green

## Enterprise gates

- [ ] `production-check` PASS
- [ ] `performance-check` PASS
- [ ] `observability-check` PASS
- [ ] `api-check` PASS
- [ ] `api-portal-check` PASS
- [ ] `security-check` PASS
- [ ] `frontend-check` PASS
- [ ] Optional: `deployment-check` PASS when `TESTING_RC_DEPLOYMENT_GATE=true`

## Production readiness

- [ ] `/health/live` and `/health/ready` OK in target environment
- [ ] `APP_KEY` set for staging/production
- [ ] Storage logs/cache writable
- [ ] Migrations current (ops-owned)

## Sign-off

| Role | Name | Date | Notes |
|---|---|---|---|
| Engineering | | | |
| QA | | | |
| Ops | | | |

Automated evaluation of the structural items above is performed by `ReleaseCandidateService::evaluateChecklist()` during `release-candidate-check`.

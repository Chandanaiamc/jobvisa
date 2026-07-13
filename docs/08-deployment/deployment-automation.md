# Deployment Automation & CI/CD (Sprint 4.4)

**Project:** JobVisa.lk Enterprise  
**Rules version:** `4.4.0`  
**Status:** Implemented

Repeatable, idempotent, fail-safe deployment automation without changing product business modules.

---

## Components

| Component | Role |
|---|---|
| Deployment Manager | Orchestrates pre → deploy → post with abort on failure |
| Release Manager | Cache clear/warm, autoload optimize, asset verify, stamp |
| Environment Validator | Env keys, debug safety, DB connectivity, writable storage |
| Backup Manager | `mysqldump` pre-deploy dumps under `storage/backups/` |
| Migration Runner | Ordered SQL + `schema_migrations` tracking (auto-baseline) |
| Health Check Runner | Live/ready + performance + observability probes |
| Rollback Manager | Plan + dry/real rollback of last migration batch |
| Deployment Audit Log | JSONL + per-run reports (secrets redacted) |
| Release Version Manager | `storage/releases/CURRENT` + version metadata |
| Maintenance Mode Manager | File flag `storage/framework/maintenance.json` (+ `APP_MAINTENANCE`) |

---

## Pipeline

**Pre-deploy:** validate env → production readiness → performance/observability status → DB backup → writable storage  

**Deploy:** enable maintenance → migrate → clear/warm cache → optimize autoload → verify assets → stamp release  

**Post-deploy:** health checks → smoke tests → disable maintenance → report  

**Rollback:** maintain maintenance → rollback last batch (and/or restore backup) → failure report  

---

## CLI

```bash
# Dry run (default / safe)
php scripts/deploy.php --dry-run

# Status
php scripts/deploy.php --status

# Real deploy (staging/production require confirmation)
php scripts/deploy.php --run --confirm=DEPLOY --version=1.0.0

# Rollback dry run
php scripts/deploy.php --rollback --dry-run

# Verification gate
php scripts/deployment-check.php
```

Composer: `composer deploy` (dry-run), `composer deployment-check`

---

## Environment flags

| Variable | Default | Purpose |
|---|---|---|
| `DEPLOY_ENABLED` | `true` | Master switch (reserved) |
| `DEPLOY_CONFIRM_TOKEN` | `DEPLOY` | Required `--confirm=` value in staging/prod |
| `DEPLOY_REQUIRE_CONFIRM` | `true` | Enforce confirmation gate |
| `DEPLOY_BACKUP_ENABLED` | `true` | Pre-deploy mysqldump |
| `DEPLOY_BACKUP_RETENTION` | `14` | Keep N newest dumps |
| `MYSQLDUMP_PATH` / `MYSQL_CLI_PATH` | auto-detect | Override binary paths |
| `DEPLOY_RUN_CHECKS` | `true` | Include perf/obs status in pre-deploy |
| `DEPLOY_OPTIMIZE_AUTOLOAD` | `true` | `composer dump-autoload -o` |
| `DEPLOY_WARM_CACHE` | `true` | Warm catalog caches |

---

## Security

- Staging/production deploys abort without `--confirm=<token>`
- Passwords never written to audit logs or reports
- Maintenance file + env toggle; health live/ready remain reachable
- CSRF, Auth, and AI modules are not modified by this sprint

---

## CI

GitHub Actions workflow `.github/workflows/ci.yml` runs production, performance, observability, and deployment checks on push/PR.

---

## Verification

```bash
E:\localhost\php\php.exe scripts/deployment-check.php
```

Expect final line: `PASS`

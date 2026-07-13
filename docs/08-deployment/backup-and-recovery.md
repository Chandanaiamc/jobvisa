# Backup and Recovery

**Project:** JobVisa.lk Enterprise

---

## What to back up

| Asset | Path / source | Frequency (recommended) |
|---|---|---|
| MySQL database | `jobvisa_db` (mysqldump) | Daily + pre-deploy |
| Uploads | `storage/uploads/` | Daily |
| Application logs (optional) | `storage/logs/` | Weekly / retain policy |
| `.env` | ops secret store only (never in Git) | On change |

---

## Database dump (example)

```bash
mysqldump -u backup_user -p --single-transaction --routines --triggers jobvisa_db > jobvisa_db_YYYYMMDD.sql
```

Automated pre-deploy dumps (Sprint 4.4): `BackupManager` via `php scripts/deploy.php` writes to `storage/backups/` (mysqldump with PDO logical fallback).

Restore:

```bash
mysql -u app_user -p jobvisa_db < jobvisa_db_YYYYMMDD.sql
```

---

## Uploads

Archive `storage/uploads` with the same timestamp as the DB dump so resume files stay consistent with rows.

---

## Pre-migration rule

Before applying new SQL migrations in staging/production:

1. Take a DB dump
2. Snapshot uploads if the migration touches file metadata
3. Apply migration
4. Verify `GET /health/ready`

---

## Disaster recovery outline

1. Provision host + PHP 8.2+ + MySQL/MariaDB
2. Deploy known release to `public/` document root
3. Restore `.env` from secret store
4. Restore database + uploads
5. Confirm `/health/live` and `/health/ready`
6. Smoke-test authentication and one critical jobseeker flow

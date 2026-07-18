# Job Applications API (Phase 1)

**Branch:** `feature/job-applications-core`  
**Option A:** API-first with `application_status_history`, live resume pointer, soft withdraw (no hard delete).

## Product rules

| Rule | Behavior |
|---|---|
| Apply | Seeker only; job must be **published** |
| Resume | Live `resume_id` (owned, not soft-deleted); defaults to primary |
| Cover letter | Optional |
| Duplicate | Unique `(job_id, user_id)` → **409** if active |
| After withdraw | Reopen via UPDATE → `submitted` |
| Withdraw | Only `submitted` or `reviewing` |
| Employer status | Matrix below; rejected may reopen to reviewing/shortlisted |
| Archive jobs | Closed/draft jobs reject new applications |

### Employer transitions

- `submitted` → reviewing, shortlisted, rejected  
- `reviewing` → shortlisted, rejected, hired  
- `shortlisted` → rejected, hired, reviewing  
- `rejected` → reviewing, shortlisted  
- `hired`, `withdrawn` → terminal  

## Endpoints

| Method | Path | Role |
|---|---|---|
| `POST` | `/api/v1/jobs/{job}/applications` | seeker |
| `GET` | `/api/v1/applications` | seeker |
| `GET` | `/api/v1/applications/{id}` | seeker |
| `POST` | `/api/v1/applications/{id}/withdraw` | seeker |
| `GET` | `/api/v1/employer/jobs/{job}/applicants` | employer |
| `GET` | `/api/v1/employer/applications/{id}` | employer |
| `POST` | `/api/v1/employer/applications/{id}/status` | employer |

## Migration

`database/migrations/067_create_application_status_history.sql` (history only; `applications` unchanged).

```bash
Get-Content database/migrations/067_create_application_status_history.sql -Raw | mysql -u root jobvisa_db
```

## Out of scope

Notifications, interviews, messaging, payments, cookie→Bearer, hard delete, CV snapshots.

## Verify

```bash
composer api-check
vendor/bin/phpunit --filter ApplicationsApi
vendor/bin/phpunit --filter 'EmployerJobsCrud|JobsPublic'
```

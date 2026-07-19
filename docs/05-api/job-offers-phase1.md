# Job Offers API (Phase 1 — Option B)

**Branch:** `feature/job-offers-core`  
**Tables:** `job_offers` (+ `job_offer_history`)  
**Does not touch:** Offer Evaluation Assistant (`offer_evaluation_*`), Interview Assistant, public jobs shapes.

## Rules

| Rule | Behavior |
|---|---|
| Create | Employer only; application must be **shortlisted**; starts as **draft** |
| Active uniqueness | At most one `draft` or `sent` offer per application |
| Expiry | Stored as **UTC** (`expires_at_utc`); auto-expire on read/respond when past |
| Accept | Sets offer `accepted` and application status **`hired`** (same transaction) |
| Soft cancel | `withdrawn` / `expired` (no hard delete) |
| Seeker visibility | Never sees `draft` offers |

### State machine

```
draft → sent | withdrawn
sent  → accepted | declined | withdrawn | expired
```

## Endpoints

| Method | Path | Role |
|---|---|---|
| `POST` | `/api/v1/employer/applications/{application}/offers` | employer |
| `GET` | `/api/v1/employer/offers` | employer |
| `GET` | `/api/v1/employer/offers/{id}` | employer |
| `POST` | `.../send` | employer |
| `POST` | `.../withdraw` | employer |
| `POST` | `.../expire` | employer |
| `GET` | `/api/v1/offers` | seeker |
| `GET` | `/api/v1/offers/{id}` | seeker |
| `POST` | `.../accept` | seeker |
| `POST` | `.../decline` | seeker |

### Create body

```json
{
  "salary_amount": 150000,
  "salary_currency": "LKR",
  "pay_period": "monthly",
  "start_date": "2026-09-01",
  "expires_at": "2026-08-20 23:59:59",
  "expires_at_is_utc": true,
  "notes": "Full-time permanent role"
}
```

## Migration

```bash
Get-Content database/migrations/069_create_job_offers.sql -Raw | E:\localhost\mysql\bin\mysql.exe -u root jobvisa_db
```

## Out of scope

PDF / offer letters, e-sign, negotiate rounds, notifications, messaging, payments, Offer Evaluation AI changes, hard delete.

## Verify

```bash
composer api-check
vendor/bin/phpunit --filter JobOffers
vendor/bin/phpunit --filter ApplicationsApi
vendor/bin/phpunit --filter InterviewScheduling
```

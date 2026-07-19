# Hiring Completion API (Phase 1 — Option B)

**Branch:** `feature/hiring-completion-core`  
**Tables:** `hire_completions` (+ `hire_completion_history`)  
**Does not touch:** Offer Evaluation AI, onboarding docs, payroll, notifications.

## Rules

| Rule | Behavior |
|---|---|
| Auto-create | `pending` row on offer **accept** and on employer direct **hired** |
| Uniqueness | One hire completion per application (`uq_hc_application`) |
| Confirm → complete | Employer only |
| On complete | Soft-cancel open interviews (`proposed\|confirmed`); if hired count ≥ `vacancies`, soft-close job |
| Offer accept guard | Application must be `shortlisted`, `reviewing`, or already `hired` |
| Soft cancel | `cancelled` status (no hard delete) |
| Seeker | Read-only list/detail |

### State machine

```
pending → confirmed | cancelled
confirmed → completed | cancelled
```

## Endpoints

| Method | Path | Role |
|---|---|---|
| `GET` | `/api/v1/employer/hire-completions` | employer |
| `GET` | `/api/v1/employer/hire-completions/{id}` | employer |
| `POST` | `.../confirm` | employer |
| `POST` | `.../complete` | employer |
| `POST` | `.../cancel` | employer |
| `GET` | `/api/v1/hire-completions` | seeker |
| `GET` | `/api/v1/hire-completions/{id}` | seeker |

### Confirm / complete body (optional)

```json
{
  "start_date": "2026-09-01",
  "notes": "Start after background check window"
}
```

## Migration

```bash
Get-Content database/migrations/070_create_hire_completions.sql -Raw | E:\localhost\mysql\bin\mysql.exe -u root jobvisa_db
```

## Out of scope

Onboarding documents, payroll, background checks, notifications, messaging, payments, e-signatures, external HR integrations, hard delete.

## Verify

```bash
composer api-check
vendor/bin/phpunit --filter HiringCompletion
vendor/bin/phpunit --filter JobOffers
vendor/bin/phpunit --filter ApplicationsApi
```

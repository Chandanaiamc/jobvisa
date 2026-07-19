# Interview Scheduling API (Phase 1 — Option B)

**Branch:** `feature/interview-scheduling-core`  
**Table:** `scheduled_interviews` (+ `scheduled_interview_history`)  
**Does not touch:** Interview Assistant (`interview_sessions`), public jobs, employer job CRUD shapes.

## Rules

| Rule | Behavior |
|---|---|
| Create | Employer only; application must be **shortlisted** |
| Active uniqueness | At most one `proposed` or `confirmed` interview per application |
| Times | Stored as **UTC** (`scheduled_at_utc`) + explicit IANA `timezone` |
| Local display | API returns `scheduled_at_local` derived from timezone |
| Soft cancel | `status=cancelled` + `cancelled_at` (no hard delete) |
| Seeker RSVP | `proposed` → confirm / decline |

### State machine

```
proposed → confirmed | declined | cancelled
proposed|confirmed → proposed (reschedule, round++)
confirmed → completed | cancelled
```

## Endpoints

| Method | Path | Role |
|---|---|---|
| `POST` | `/api/v1/employer/applications/{application}/interviews` | employer |
| `GET` | `/api/v1/employer/interviews` | employer |
| `GET` | `/api/v1/employer/interviews/{id}` | employer |
| `POST` | `.../reschedule` | employer |
| `POST` | `.../cancel` | employer |
| `POST` | `.../complete` | employer |
| `GET` | `/api/v1/interviews` | seeker |
| `GET` | `/api/v1/interviews/{id}` | seeker |
| `POST` | `.../confirm` | seeker |
| `POST` | `.../decline` | seeker |

### Schedule body

```json
{
  "scheduled_at": "2026-08-01 10:00:00",
  "timezone": "Asia/Colombo",
  "duration_minutes": 60,
  "location_type": "phone",
  "location_notes": "WhatsApp"
}
```

Use `"scheduled_at_is_utc": true` (or `scheduled_at_utc`) when the timestamp is already UTC.

## Migration

```bash
Get-Content database/migrations/068_create_scheduled_interviews.sql -Raw | mysql -u root jobvisa_db
```

## Out of scope

Video providers, notifications, messaging, payments, external calendar sync, offers/hiring, hard delete.

## Verify

```bash
composer api-check
vendor/bin/phpunit --filter InterviewScheduling
vendor/bin/phpunit --filter ApplicationsApi
```

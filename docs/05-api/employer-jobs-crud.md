# Employer Jobs CRUD API (Phase 1)

**Branch:** `feature/employer-jobs-crud`  
**Scope:** Authenticated employer create / read / update / publish / unpublish / archive for **owned** jobs.

**Out of scope:** Apply flow, cookie→Bearer bridge, session HTML forms, hard delete, schema migration, admin moderation (`pending` / `rejected`).

---

## Rules

| Rule | Behavior |
|---|---|
| Auth | Bearer + `api.employer` role |
| Ownership | `jobs.employer_id` → `employers.user_id = actor.id` |
| Self-publish | `draft` → `published` (also create with `status=published`) |
| Unpublish | → `draft` |
| Archive | → `closed` (soft archive; no hard delete) |
| Public API | Unchanged; only `published` jobs appear on `GET /api/v1/jobs` |

---

## Endpoints

| Method | Path | Notes |
|---|---|---|
| `GET` | `/api/v1/employer/jobs` | List owned (all statuses) |
| `GET` | `/api/v1/employer/jobs/{job}` | Owned detail |
| `POST` | `/api/v1/employer/jobs` | Create (`status`: `draft` \| `published`) |
| `POST` | `/api/v1/employer/jobs/{job}` | Update owned fields |
| `POST` | `/api/v1/employer/jobs/{job}/publish` | Publish |
| `POST` | `/api/v1/employer/jobs/{job}/unpublish` | Unpublish |
| `POST` | `/api/v1/employer/jobs/{job}/archive` | Soft archive (`closed`) |

Existing: applicants + ranking GETs unchanged.

---

## Create body (required)

`title`, `description`, `category_id`, `job_type_id`, `country_id`  
Optional: `city_id`, salaries, `visa_sponsorship`, `requirements`, `benefits`, `vacancies`, `status`, `slug`, …

---

## Service stack

`EmployerJobsService` + `JobPolicy` + `JobValidator` + `JobRepository` write helpers.  
Session MVC ranking pages remain; they do not yet expose create/edit forms (Option A = API-first).

---

## Verify

```bash
composer api-check
vendor/bin/phpunit --filter EmployerJobsCrud
vendor/bin/phpunit --filter JobsPublic
```

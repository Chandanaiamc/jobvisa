# Frontend API Jobs Integration (Phase 1 + 2)

**Branch:** `feature/frontend-api-jobs-integration`  
**Scope:** Public job listing, detail, search, filters, pagination — hybrid SSR + progressive enhancement.

**Out of scope:** Employer create/edit/publish, seeker apply, cookie→Bearer bridge for authenticated job APIs.

---

## Architecture

| Layer | Role |
|---|---|
| API | `GET /api/v1/jobs` (filters + pagination), `GET /api/v1/jobs/{id}` |
| Service | `PublicJobsService` shared by API + SSR |
| SSR | `GET /jobs`, `GET /jobs/{job}` via `PagesController` |
| PE | `public/assets/js/jobs-api.js` updates list via API without full reload |

Authenticated employer/seeker job flows remain **session MVC**.

---

## API query parameters (`GET /api/v1/jobs`)

| Param | Notes |
|---|---|
| `q` | Keyword (FULLTEXT when ≥3 chars, else LIKE) |
| `country_id` | Filter by country |
| `job_type_id` | Filter by job type |
| `page` | 1-based page (default 1) |
| `per_page` | Page size 1–100 (default 20) |
| `limit` | **Backward compatible** alias for `per_page` |
| `include_filters=1` | Adds `meta.filter_options` (countries, job_types) |

### Response meta

```json
{
  "pagination": { "page": 1, "per_page": 20, "total": 42, "total_pages": 3 },
  "filters_applied": { "q": "", "country_id": null, "job_type_id": null },
  "count": 20
}
```

### Public job fields

List cards: enriched `jobPublic` (summary from description, salary, visa, ids…).  
Detail (`detailed=true`): also `description`, `requirements`, `benefits`, etc.  
`summary` kept for older clients.

---

## Frontend files

| File | Purpose |
|---|---|
| `app/views/pages/jobs/index.php` | Listing entry |
| `app/views/pages/jobs/list.php` | Filters + SSR results |
| `app/views/pages/jobs/show.php` / `detail.php` | Detail |
| `app/views/layouts/public.php` | Public layout + assets |
| `public/assets/css/public.css` | Public jobs styling |
| `public/assets/js/jobs-api.js` | Progressive enhancement |

No-JS: form GET to `/jobs` still works (full SSR).

---

## Verify

```bash
composer api-check
vendor/bin/phpunit --filter JobsPublic
# Manual: open /jobs, search, paginate, open a job detail
```

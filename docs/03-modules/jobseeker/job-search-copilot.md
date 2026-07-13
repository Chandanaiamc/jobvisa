# AI Job Search Copilot (Sprint 3.8)

Generates a deterministic job-search strategy and ranked shortlist from resume, skills, career goal, published jobs and prior AI signals. **No external AI APIs.** PHP views.

---

## Capabilities

- Search query suggestions
- Recommended filters (seniority, experience, locations, keywords, salary floor)
- Ranked recommendations (safe-fit / stretch / hidden-gem)
- Match reasons and apply urgency
- Application priority order
- Weekly search plan
- Alert keywords and strategy tips
- Copilot score
- Generate / recalculate, PDF export
- History (soft delete, restore, purge, clear)

Rules version: `3.8.0`

---

## Routes

| Method | Path |
|---|---|
| GET | `/jobseeker/resumes/{id}/job-search-copilot` |
| POST | `…/generate` · `…/recalculate` |
| GET | `…/history` |
| POST | `…/history/{historyId}/delete` · `…/restore` · `…/purge` · `…/clear` |
| GET | `…/plans/{planId}/export/pdf` |

Jobseeker ownership + CSRF on POSTs. Employer / non-owner denied.

---

## Persistence

Migration `062_create_job_search_copilot_tables.sql`:

- `job_search_copilot_plans`
- `job_search_copilot_history`

Complements Job Matching. Preserves prior AI modules including Mock Interview Simulator.

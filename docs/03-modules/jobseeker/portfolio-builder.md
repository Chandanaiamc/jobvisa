# AI Portfolio & Project Builder (Sprint 3.6)

Generates recruiter-ready portfolio project plans from resume, skill-gap, learning path and job-match signals. Deterministic heuristics only. **No external AI APIs.** PHP views.

---

## Capabilities

- Portfolio strength score and recruiter evaluation
- Priority project recommendations (GitHub, full-stack, mobile, UI/UX, data/AI)
- Case study + STAR achievement generators
- Resume-ready project descriptions
- Skills, difficulty, estimated weeks, repo ideas
- Generate / recalculate, PDF export
- History (soft delete, restore, purge, clear)

Rules version: `3.6.0`

---

## Routes

| Method | Path |
|---|---|
| GET | `/jobseeker/resumes/{id}/portfolio-builder` |
| POST | `…/generate` · `…/recalculate` |
| GET | `…/history` |
| POST | `…/history/{historyId}/delete` · `…/restore` · `…/purge` · `…/clear` |
| GET | `…/plans/{planId}/export/pdf` |

Jobseeker ownership + CSRF on POSTs. Employer / non-owner denied.

---

## Persistence

Migration `060_create_portfolio_builder_tables.sql`:

- `portfolio_builder_plans`
- `portfolio_builder_history`

Preserves prior AI modules including Learning Path Generator.

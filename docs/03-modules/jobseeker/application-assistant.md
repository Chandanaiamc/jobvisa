# AI Application Assistant (Sprint 3.2)

Pre-apply readiness analysis: resume vs published job. Deterministic heuristics only. **No external AI APIs.** PHP views.

---

## Capabilities

- Resume vs job comparison
- Overall application readiness score (0–100)
- Skill / experience / education / certification / portfolio match
- Missing skills and ATS keywords
- Strengths, weaknesses, recommendations
- One-click links to AI Resume Builder and Cover Letter Generator
- Analysis versions + history (soft delete, restore, permanent purge)
- Recalculate and PDF export

Rules version: `3.2.0`

---

## Routes

| Method | Path |
|---|---|
| GET | `/jobseeker/jobs/{job}/application-assistant` |
| POST | `…/analyze` · `…/recalculate` |
| GET | `…/history` |
| POST | `…/history/{history}/delete` · `…/restore` · `…/purge` · `…/clear` |
| GET | `…/analyses/{analysisId}/export/pdf` |

Jobseeker ownership + CSRF on POSTs. Employer / non-owner denied.

---

## Persistence

Migration `056_create_application_assistant_tables.sql`:

- `application_assistant_analyses`
- `application_assistant_history`

Preserves Intelligence, Matching, Ranking, Dashboard, Recruiter, Interview, Career Coach, Resume Builder and Cover Letter modules.

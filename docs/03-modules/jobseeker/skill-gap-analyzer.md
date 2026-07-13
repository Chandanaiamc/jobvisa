# AI Skill Gap Analyzer (Sprint 3.4)

Compare a resume against a target published job and produce a deterministic skill-gap analysis with learning recommendations. **No external AI APIs.** PHP views.

---

## Capabilities

- Resume vs job skill comparison
- Matching / missing skills
- Strengths and weaknesses
- Skill gap percentage and career readiness score
- Priority learning order, roadmap, certifications, courses
- AI explanation
- Analyze / recalculate
- History (soft delete, restore, purge, clear)
- PDF export

Rules version: `3.4.0`

---

## Routes

| Method | Path |
|---|---|
| GET | `/jobseeker/resumes/{id}/skill-gap` |
| POST | `…/analyze` · `…/recalculate` |
| GET | `…/history` |
| POST | `…/history/{historyId}/delete` · `…/restore` · `…/purge` · `…/clear` |
| GET | `…/analyses/{analysisId}/export/pdf` |

Jobseeker ownership + CSRF on POSTs. Employer / non-owner denied.

---

## Persistence

Migration `058_create_skill_gap_tables.sql`:

- `skill_gap_analyses`
- `skill_gap_history`

Reuses Job Matching signals. Preserves prior AI modules including Salary Intelligence.

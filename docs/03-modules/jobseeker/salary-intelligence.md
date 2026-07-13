# AI Salary Intelligence (Sprint 3.3)

Predict expected salary ranges from resume signals and published job salary bands. Deterministic heuristics only. **No external AI APIs.** PHP views.

---

## Capabilities

- AI predicted salary + min / max range
- Market average and recommended target
- Confidence score and salary explanation
- Skill / experience / education / certification / location / industry impact
- Negotiation tips
- Calculate / recalculate
- Prediction versions + salary history (soft delete, restore, purge, clear)
- PDF export

Rules version: `3.3.0`

---

## Routes

| Method | Path |
|---|---|
| GET | `/jobseeker/resumes/{id}/salary-intelligence` |
| POST | `…/calculate` · `…/recalculate` |
| GET | `…/history` |
| POST | `…/history/{historyId}/delete` · `…/restore` · `…/purge` · `…/clear` |
| GET | `…/predictions/{predictionId}/export/pdf` |

Jobseeker ownership + CSRF on POSTs. Employer / non-owner denied.

---

## Persistence

Migration `057_create_salary_intelligence_tables.sql`:

- `salary_intelligence_predictions`
- `salary_intelligence_history`

Preserves Intelligence, Matching, Ranking, Dashboard, Recruiter, Interview, Career Coach, Resume Builder, Cover Letter and Application Assistant modules.

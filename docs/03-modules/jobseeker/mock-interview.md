# AI Mock Interview Simulator (Sprint 3.7)

Generates realistic mock interview sessions from resume, target job, skills, experience, career level and prior AI signals. Deterministic heuristics only. **No external AI APIs.** PHP views.

---

## Capabilities

- HR, technical, behavioral and scenario questions
- STAR-oriented answer analysis
- Communication, technical, confidence and overall scores
- Improvement suggestions and follow-up questions
- Interview summary / report and PDF export
- Generate / analyze / recalculate
- History (soft delete, restore, purge, clear)

Rules version: `3.7.0`

---

## Routes

| Method | Path |
|---|---|
| GET | `/jobseeker/resumes/{id}/mock-interview` |
| POST | `…/generate` · `…/analyze` · `…/recalculate` |
| GET | `…/history` |
| POST | `…/history/{historyId}/delete` · `…/restore` · `…/purge` · `…/clear` |
| GET | `…/sessions/{sessionId}/export/pdf` |

Jobseeker ownership + CSRF on POSTs. Employer / non-owner denied.

---

## Persistence

Migration `061_create_mock_interview_tables.sql`:

- `mock_interview_sessions`
- `mock_interview_history`

Preserves prior AI modules including Portfolio & Project Builder.

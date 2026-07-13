# AI Interview Assistant (Sprint 2F.7)

Deterministic interview preparation for employers from resume data, AI scores, and job requirements. **No external AI APIs.**

---

## Capabilities

- Generate technical and behavioral interview questions
- Produce candidate strengths, weaknesses, and interviewer recommendations
- Persist interview sessions (soft-deletable history)
- Capture interview scorecards (technical, behavioral, communication, culture fit)
- Employer-only authorization with CSRF on POSTs

Rules version: `2F.7.0`

---

## Routes

| Method | Path |
|---|---|
| GET | `/employer/interview-assistant` |
| POST | `/employer/interview-assistant/generate` |
| GET | `/employer/interview-assistant/sessions/{sessionId}` |
| POST | `/employer/interview-assistant/sessions/{sessionId}/scorecard` |
| POST | `/employer/interview-assistant/sessions/{sessionId}/delete` |
| POST | `/employer/interview-assistant/history/clear` |

---

## Persistence

Migration `052_create_interview_assistant_tables.sql`:

- `interview_sessions` — questions, insights, context scores, soft delete
- `interview_scorecards` — per-session scorecard (upsert)

Preserves Resume Intelligence, Matching, Ranking, Recruiter Assistant, and Employer AI Dashboard modules.

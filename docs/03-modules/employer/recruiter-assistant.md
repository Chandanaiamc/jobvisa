# AI Recruiter Assistant (Sprint 2F.6)

Deterministic natural-language candidate search for employers. **No external AI APIs.**

---

## Capabilities

- Parse NL queries into skills, experience, education, certifications, location, AI match / ranking floors
- Search applicants on employer-owned jobs
- Recommend top candidates from results
- Save search history (soft delete)
- Recruiter suggestions (contextual next queries)

Rules version: `2F.6.0`

---

## Routes

| Method | Path |
|---|---|
| GET | `/employer/recruiter-assistant` |
| POST | `/employer/recruiter-assistant/search` |
| POST | `/employer/recruiter-assistant/history/{historyId}/delete` |
| POST | `/employer/recruiter-assistant/history/clear` |

Employer role + CSRF on POSTs.

---

## Persistence

Migration `051_create_recruiter_search_history.sql` — `recruiter_search_history` (FK → users, soft delete).

Preserves Resume Intelligence, Matching, Ranking, and Employer AI Dashboard modules.

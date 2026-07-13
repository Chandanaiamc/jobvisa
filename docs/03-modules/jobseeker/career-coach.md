# AI Career Coach (Sprint 2F.8)

Deterministic career coaching for jobseekers from resume intelligence, scores, skills, experience, education, certifications, projects, achievements and job-match data. **No external AI APIs.**

---

## Capabilities

- Personalized coaching summary and readiness label
- Skill-gap analysis (intelligence + job-match signals)
- Next-best-role recommendations
- Prioritized learning roadmap
- Certification and portfolio improvement recommendations
- Suitable job opportunities from existing match snapshots
- Coaching session snapshot + recommendation history
- Soft delete and restore for history
- Refresh/recalculate with CSRF

Rules version: `2F.8.0`

---

## Routes

| Method | Path |
|---|---|
| GET | `/jobseeker/resumes/{id}/career-coach` |
| POST | `/jobseeker/resumes/{id}/career-coach/recalculate` |
| POST | `/jobseeker/resumes/{id}/career-coach/history/{historyId}/delete` |
| POST | `/jobseeker/resumes/{id}/career-coach/history/{historyId}/restore` |
| POST | `/jobseeker/resumes/{id}/career-coach/history/clear` |

Jobseeker ownership via resume policy. Employer / non-owner access denied. CSRF on POSTs.

---

## Persistence

Migration `053_create_career_coach_tables.sql`:

- `career_coach_sessions` — current coaching pack per resume
- `career_coach_history` — recommendation history (soft delete + restore)

Preserves Resume Intelligence, Scoring, Matching, Ranking, Dashboard, Recruiter Assistant and Interview Assistant modules.

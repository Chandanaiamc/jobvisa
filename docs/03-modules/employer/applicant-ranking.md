# Applicant Ranking Engine (Sprint 2F.4)

Deterministic employer-side ranking of job applicants — **no external AI APIs**.

---

## Score weights (sum = 100)

| Signal | Weight | Source |
|---|---|---|
| Resume score | 20 | `resume_intelligence_snapshots.overall_score` |
| Job match | 30 | `resume_job_match_snapshots` (computed if missing) |
| Skills | 12 | Match skills / resume skills depth |
| Experience | 10 | Match experience |
| Education | 8 | Match education |
| Certifications | 7 | Match certs / resume certifications |
| Portfolio | 7 | Visible portfolio items |
| References | 6 | Contactable references |

Rules version: `2F.4.0`

---

## Routes (employer + CSRF)

| Method | Path |
|---|---|
| GET | `/employer/jobs` |
| GET | `/employer/jobs/{job}/applicants/ranking` |
| POST | `/employer/jobs/{job}/applicants/ranking/recalculate` |
| GET | `/employer/jobs/{job}/applicants/ranking/history` |
| POST | `/employer/jobs/{job}/applicants/ranking/history/{historyId}/delete` |
| POST | `/employer/jobs/{job}/applicants/ranking/history/clear` |

Ownership: `jobs.employer_id` → `employers.user_id` = actor id; role must be `employer`.

---

## Persistence

Migration `050_create_job_applicant_rankings.sql`:

- `job_applicant_rankings` — current ranks (unique job+application, soft delete)
- `job_applicant_ranking_history` — append-on-recalculate history (soft delete)

---

## UI

Filter by status / min score / search; sort by rank, overall, match, resume, applied date. Top candidates table + history page.

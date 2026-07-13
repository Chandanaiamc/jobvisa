# Employer AI Dashboard (Sprint 2F.5)

Deterministic hiring insights for employers — aggregates rankings, job matches, and applications. **No new tables** (reads existing 2F.3/2F.4 data).

---

## Surfaces

| Metric / panel | Source |
|---|---|
| Hiring health score | Pipeline + match + interview-ready heuristics |
| Average AI match | `resume_job_match_snapshots` |
| Average ranking | `job_applicant_rankings` |
| Top ranked candidates | Rankings ordered by overall |
| Interview-ready | Overall ≥70 and match ≥55 |
| Skill gap analytics | Missing required skills from match explanations |
| Charts | Match-by-job, status mix, score bands |

---

## Routes

| Method | Path | CSRF |
|---|---|---|
| GET | `/employer` | — |
| POST | `/employer/ai-dashboard/refresh` | Yes |

Policy: actor `role=employer` only.

---

## Architecture

`EmployerAiDashboardService` · `EmployerDashboardPolicy` · `EmployerAiDashboardDTO` · `AiDashboardController` · `views/employer/pages/ai-dashboard.php`

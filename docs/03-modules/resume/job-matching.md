# Job Matching Foundation (Sprint 2F.3)

Deterministic, explainable resume↔job matching — **no external AI APIs**.

---

## Principles

- Reuses existing `jobs`, resume section tables, skill/language catalogues
- Does **not** alter resume completion percentage
- Scores clamped 0–100; rules version `2F.3.0`
- Published jobs only; resume ownership enforced
- CSRF on POST recalculate

---

## Score weights (sum = 100)

| Category | Weight |
|---|---|
| Skills | 35 |
| Experience | 20 |
| Education | 15 |
| Languages | 10 |
| Certifications | 10 |
| Location / work preference | 10 |

Job skills/languages/certs are inferred from `jobs.requirements` + description against catalogues (no duplicate `job_skills` tables in this sprint).

---

## Architecture

| Layer | Location |
|---|---|
| DTOs | `app/Domain/JobMatching/DTO/*` |
| Extractor / scoring / explanation | `JobRequirementExtractor`, `JobMatchScoringService`, `JobMatchExplanationService` |
| Context factory | `JobMatchContextFactory` |
| Policy / validator | `JobMatchPolicy`, `JobMatchValidator` |
| Service | `JobMatchService` |
| Snapshot repo | `ResumeJobMatchRepository` → `resume_job_match_snapshots` |
| Controller / views | `JobMatchController`, `job-match.php`, `recommended-jobs.php` |

---

## Routes

| Method | Path |
|---|---|
| GET | `/jobseeker/resumes/{resume}/jobs/{job}/match` |
| POST | `/jobseeker/resumes/{resume}/jobs/{job}/match/recalculate` |
| GET | `/jobseeker/resumes/{resume}/recommended-jobs` |

---

## Persistence

Migration `049_create_resume_job_match_snapshots.sql` — unique `(resume_id, job_id)`, soft delete, FKs to `resumes` and `jobs`.

---

## Rollback

`049_create_resume_job_match_snapshots_rollback.sql` drops only the snapshot table.

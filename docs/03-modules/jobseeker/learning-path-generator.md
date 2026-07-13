# AI Learning Path Generator (Sprint 3.5)

Personalized learning roadmaps from resume, skill-gap, salary intelligence and career-coach signals. Deterministic heuristics only. **No external AI APIs.** PHP views.

---

## Capabilities

- Beginner → Intermediate → Advanced roadmap
- Weekly schedule and estimated timeline
- Priority learning sequence
- Courses, certifications, books, YouTube, practice projects, portfolio tips
- Milestone tracking with progress percentage
- Career goal alignment score
- Generate / recalculate, PDF export
- History (soft delete, restore, purge, clear)

Rules version: `3.5.0`

---

## Routes

| Method | Path |
|---|---|
| GET | `/jobseeker/resumes/{id}/learning-path` |
| POST | `…/generate` · `…/recalculate` |
| POST | `…/paths/{pathId}/milestones` |
| GET | `…/history` |
| POST | `…/history/{historyId}/delete` · `…/restore` · `…/purge` · `…/clear` |
| GET | `…/paths/{pathId}/export/pdf` |

Jobseeker ownership + CSRF on POSTs. Employer / non-owner denied.

---

## Persistence

Migration `059_create_learning_path_tables.sql`:

- `learning_paths`
- `learning_path_history`

Preserves prior AI modules including Skill Gap Analyzer and Salary Intelligence.

# AI Resume Builder (Sprint 2F.9)

Deterministic ATS-oriented resume content generation for jobseekers. **No external AI APIs.** PHP views (not Blade).

---

## Capabilities

- Professional summary generation
- Work experience bullet rewrites
- Technical and soft skill suggestions
- Education and certification description improvements
- ATS-friendly document content
- ATS optimization score (0–100)
- Missing keywords from matched jobs + optimization suggestions
- Multiple AI resume versions with preview-before-save
- Generation history with soft delete and restore
- Regenerate / save / activate actions

Rules version: `2F.9.0`

---

## Routes

| Method | Path |
|---|---|
| GET | `/jobseeker/resumes/{id}/ai-builder` |
| POST | `/jobseeker/resumes/{id}/ai-builder/generate` |
| POST | `/jobseeker/resumes/{id}/ai-builder/regenerate` |
| POST | `…/versions/{versionId}/save` |
| POST | `…/versions/{versionId}/activate` |
| POST | `…/versions/{versionId}/delete` |
| POST | `…/history/{historyId}/delete` |
| POST | `…/history/{historyId}/restore` |
| POST | `…/history/clear` |

Jobseeker ownership via resume policy. CSRF on all POSTs.

---

## Persistence

Migration `054_create_ai_resume_builder_tables.sql`:

- `ai_resume_versions` — preview/saved versions (soft delete)
- `ai_resume_builder_history` — generation history (soft delete + restore)

Preserves Career Coach, Interview Assistant, Recruiter Assistant, Dashboard, Ranking, Matching, Scoring and Intelligence modules.

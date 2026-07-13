# AI Cover Letter Generator (Sprint 3.1)

Deterministic multi-style cover letters for jobseekers from resume, intelligence scores, career coach signals and job-match data. **No external AI APIs.** PHP views (not Blade).

---

## Capabilities

- Styles: professional, executive, graduate, technical, creative
- Tone regeneration
- Matching skills & achievements highlights
- ATS-friendly body + ATS score
- Preview before save
- Version history with soft delete, restore and permanent purge
- PDF and DOCX export (dependency-free)
- Copy to clipboard

Rules version: `3.1.0`

---

## Routes

| Method | Path |
|---|---|
| GET | `/jobseeker/resumes/{id}/cover-letters` |
| POST | `…/generate` · `…/regenerate` |
| POST | `…/versions/{versionId}/save` · `…/delete` |
| GET | `…/versions/{versionId}/export/pdf` · `…/export/docx` |
| POST | `…/history/{historyId}/delete` · `…/restore` · `…/purge` · `…/history/clear` |

Jobseeker ownership + CSRF on POSTs.

---

## Persistence

Migration `055_create_cover_letter_tables.sql`:

- `cover_letter_versions`
- `cover_letter_history` (soft delete + restore + hard purge)

Preserves Intelligence, Scoring, Matching, Ranking, Dashboard, Recruiter, Interview, Career Coach and Resume Builder modules.

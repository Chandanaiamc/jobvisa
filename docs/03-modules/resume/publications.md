# Resume Publications & Research (Sprint 2E.2)

Resume-scoped professional, academic, technical, and media publications for the Enterprise Resume Builder.

---

## Architecture

| Layer | Class |
|---|---|
| Controller | `ResumePublicationController` |
| Service | `ResumePublicationService` |
| DTO / Validator / Policy | `ResumePublication*` |
| Repository | `ResumePublicationRepository` (+ interface) |

Mirrors Education → Achievements patterns. Auth and prior resume modules untouched.

---

## Migration

- `044_create_resume_publications.sql` / `044_create_resume_publications_rollback.sql`
- Isolated table `resume_publications`
- FK → `resumes` (CASCADE), optional FK → `resume_projects`, `countries`, `cities`
- Soft delete via `deleted_at`; document files retained on soft delete

---

## Routes

| Method | Path |
|---|---|
| GET/POST | `/jobseeker/resumes/{id}/publications` |
| GET | `.../search` · `.../cities` |
| POST | `.../reorder` |
| GET/POST | `.../{publication}/edit` · update |
| POST | `.../delete` · `.../restore` |
| POST | `.../document` · `.../remove-document` |
| GET | `.../download` |

CSRF on all POSTs. Owner-only via `ResumePublicationPolicy`.

---

## Features

- CRUD, soft delete, restore, reorder
- Search (title, publisher, authors, DOI, ISBN, ISSN, patent, conference, keywords)
- Filter (type, year, peer reviewed, featured, visibility, status, country)
- Sort (newest, oldest, title, year, featured, sort order)
- Pagination (10 per page)
- Country → city dependent select
- Associated project (same resume only)
- Document upload: PDF/DOC/DOCX/JPG/PNG ≤ 10MB; random stored names; private path; authorized download only
- Public projection: `visibility=public` + `status=active` only; no internal paths; employers/private excluded

---

## Completion weights (total **100**)

Rebalanced to add Publications (5) without dropping categories:

| Section | Weight |
|---|---|
| Title | 8 |
| Personal | 18 |
| Professional | 13 |
| CV | 7 |
| Education | 10 |
| Experience | 10 |
| Skills | 6 |
| Languages | 5 |
| Certifications | 5 |
| Projects | 8 |
| Achievements | 5 |
| **Publications** | **5** |

Complete when ≥1 non-deleted `resume_publications` row.

---

## Security

- Prepared statements; output escaped; CSRF on mutations
- IDOR prevented by resume + publication ownership checks
- Employers cannot edit; deny by default
- Soft delete does not destroy uploaded files; replace/remove cleans safely

---

## Rollback

Run `044_create_resume_publications_rollback.sql` (`DROP TABLE resume_publications`). Prior modules unaffected.

# Resume Projects & Portfolio (Sprint 2D.9)

Resume-scoped projects and portfolio entries for the Enterprise Resume Builder.

---

## Architecture audit

| Concern | Finding |
|---|---|
| Prior resume projects | None — new `resume_projects` |
| Profile modules | Unchanged |
| Public profile | `listPublic` / `toPublicArray` — private projects and documents excluded |

**Source of truth:** `resume_projects` keyed by `resume_id`.

---

## Migration

- `041_create_resume_projects.sql` / rollback
- Fields: title, client, organization, role, description, technologies (JSON), URLs, image, document, dates, currently working, team size, category, industry, location, achievements, responsibilities, status, visibility, sort order, soft delete

---

## Routes

| Method | Path |
|---|---|
| GET/POST | `/jobseeker/resumes/{id}/projects` |
| POST | `.../projects/reorder` |
| GET/POST | `.../projects/{project}/edit` / update |
| POST | `.../delete` · `.../restore` |
| POST/GET | `.../image` · `.../image/delete` · `.../image/download` |
| POST/GET | `.../document` · `.../document/delete` · `.../document/download` |

CSRF on POSTs. Auth unchanged. Owner-only via `ResumeProjectPolicy`.

---

## Components

| Layer | Class |
|---|---|
| Controller | `ResumeProjectController` |
| Service | `ResumeProjectService` |
| DTO / Validator / Policy | `ResumeProject*` |
| Repository | `ResumeProjectRepository` |

---

## Public profile rules

- Only `visibility = public`, `status = active`, non-deleted rows
- `toPublicArray()` returns `null` for private/deleted
- Document paths never included in public projection
- Owner downloads remain available via authenticated routes

---

## Completion

Rebalanced weights (total **100** — see `achievements.md` for Sprint 2E.1):

| Section | Weight |
|---|---|
| Title | 8 |
| Personal | 19 |
| Professional | 14 |
| CV | 7 |
| Education | 11 |
| Experience | 11 |
| Skills | 7 |
| Languages | 5 |
| Certifications | 5 |
| **Projects** | **8** |
| Achievements | 5 |

Complete when ≥1 non-deleted `resume_projects` row.

---

## Manual testing

1. Add project with title, category, tech tags, public visibility  
2. Toggle currently working → end date cleared  
3. Drag reorder / ↑↓; soft-delete → restore  
4. Upload image + PDF; private project hides from `listPublic`  
5. Completion includes projects weight (total still 100)  
6. Prior resume sections + auth intact  

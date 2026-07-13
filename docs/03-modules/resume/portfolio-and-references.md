# Resume Portfolio & References (Sprint 2E.3)

Resume-scoped professional portfolio items and professional references for the Enterprise Resume Builder.

---

## Modules

| Module | Table(s) | Path |
|---|---|---|
| Portfolio | `resume_portfolios`, `resume_portfolio_gallery` | `/jobseeker/resumes/{id}/portfolio` |
| References | `resume_references` | `/jobseeker/resumes/{id}/references` |

---

## Architecture

| Layer | Portfolio | References |
|---|---|---|
| Controller | `ResumePortfolioController` | `ResumeReferenceController` |
| Service | `ResumePortfolioService` | `ResumeReferenceService` |
| DTO / Validator / Policy | `ResumePortfolio*` | `ResumeReference*` |
| Repository | `ResumePortfolioRepository` | `ResumeReferenceRepository` |

Auth and prior resume modules are unchanged.

---

## Migrations

- `045_create_resume_portfolios.sql` / rollback (drops gallery then portfolios)
- `046_create_resume_references.sql` / rollback
- Isolated creates; FK → `resumes`, optional → `resume_projects`, `countries`, `cities`
- Soft delete via `deleted_at`; gallery soft-deleted independently; featured image files retained on soft delete of portfolio

---

## Portfolio features

- CRUD, soft delete, restore, reorder, search/filter/sort, pagination
- Category, description, portfolio / GitHub / Behance / Dribbble / Figma / YouTube / Google Drive URLs
- Featured image upload/replace/remove/download (JPG/PNG/WebP ≤ 5MB)
- Gallery images (max 12) with soft delete
- Country/city, associated project, featured, visibility (`public` / `employers` / `private`), status
- `listPublic` / `listForEmployer` + DTO projections

---

## References features

- CRUD, soft delete, restore, reorder, search/filter/sort, pagination
- Name, designation, company, email, phone, relationship, years known, permission to contact
- Country/city, associated project, featured, visibility (default **private**), status
- Public projection never exposes email/phone
- Employer projection includes contact only when `permission_to_contact` is true and visibility is public or employers

---

## Completion weights (total **100**)

| Section | Weight |
|---|---|
| Title | 8 |
| Personal | 15 |
| Professional | 12 |
| CV | 7 |
| Education | 9 |
| Experience | 9 |
| Skills | 5 |
| Languages | 5 |
| Certifications | 5 |
| Projects | 7 |
| Achievements | 5 |
| Publications | 5 |
| **Portfolio** | **4** |
| **References** | **4** |

Complete when ≥1 non-deleted row in the respective table.

---

## Security

- Owner-only mutations; CSRF on POSTs; prepared statements; escaped output
- IDOR prevented by resume + record ownership
- Employers cannot edit
- Private paths for uploads; authorized download routes only

---

## Rollback

1. `046_create_resume_references_rollback.sql`
2. `045_create_resume_portfolios_rollback.sql`

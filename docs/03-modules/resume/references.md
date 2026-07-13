# Resume Professional References (Sprint 2E.4)

Enterprise professional references for the Resume Builder. Hardens the module introduced in Sprint 2E.3 (migration `046`).

---

## Architecture

| Layer | Class |
|---|---|
| Controller | `ResumeReferenceController` |
| Service | `ResumeReferenceService` |
| DTO / Validator / Policy | `ResumeReference*` |
| Repository | `ResumeReferenceRepository` (+ interface) |

---

## Migration

- `046_create_resume_references.sql` / rollback (already applied in 2E.3)
- Table: `resume_references`
- Sprint 2E.4 makes **no schema change** (no breaking migrations)
- Position maps to column `designation` (UI label: Position; form accepts `position` alias)

---

## Fields

| Field | Notes |
|---|---|
| Name * | Required |
| Position | Stored as `designation` |
| Company | |
| Relationship | Taxonomy + custom allowed |
| Email / Phone | Visibility-gated |
| Years known | 0–99.9 |
| Permission to contact | Required for employer contact exposure |
| Country / City | City must belong to country |
| Associated project | Same resume only |
| Visibility | `public` · `employers` · `private` (default) |
| Status | `active` · `hidden` |
| Featured / Sort order | |
| Soft delete | Trash + restore |

---

## Features

- Create / Update / Soft delete / Restore / Reorder
- Search (name, company, position, email, relationship)
- Filters (relationship, featured, visibility, status, country, permission to contact, sort)
- Pagination
- Public projection: never email/phone
- Employer projection: contact only when permission + visibility ∈ {public, employers}
- Owner-only mutations; CSRF; prepared statements

---

## Routes

| Method | Path |
|---|---|
| GET/POST | `/jobseeker/resumes/{id}/references` |
| GET | `.../search` · `.../cities` |
| POST | `.../reorder` |
| GET/POST | `.../{reference}/edit` · update |
| POST | `.../delete` · `.../restore` |

---

## Completion

Weight **4** (`WEIGHT_REFERENCES`). Complete when ≥1 non-deleted reference exists. Total resume weights remain **100**.

---

## Rollback

Run `046_create_resume_references_rollback.sql` only if removing the entire module (drops `resume_references`).

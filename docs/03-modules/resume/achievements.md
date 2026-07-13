# Resume Awards & Achievements (Sprint 2E.1)

Resume-scoped awards and achievements for the Enterprise Resume Builder.

---

## Architecture audit

| Concern | Finding |
|---|---|
| Prior resume achievements | None — new `resume_achievements` |
| Associated project | Optional FK → `resume_projects` (`ON DELETE SET NULL`) |
| Public profile | `listPublic` / `toPublicArray` — private rows & certificates excluded |

---

## Migration

- `042_create_resume_achievements.sql` / rollback
- Fields: title, issuer, description, type, date, credential URL, certificate path, project_id, featured, visibility, sort order, status, soft delete

---

## Routes

| Method | Path |
|---|---|
| GET | `/jobseeker/resumes/{id}/achievements` (`?q=` search) |
| GET | `/jobseeker/resumes/{id}/achievements/search` |
| POST | `/jobseeker/resumes/{id}/achievements` |
| POST | `.../reorder` |
| GET/POST | `.../{achievement}/edit` / update |
| POST | `.../delete` · `.../restore` |
| POST/GET | `.../certificate` · delete · download |

CSRF on POSTs. Owner-only via `ResumeAchievementPolicy`. Auth unchanged.

---

## Components

| Layer | Class |
|---|---|
| Controller | `ResumeAchievementController` |
| Service | `ResumeAchievementService` |
| DTO / Validator / Policy | `ResumeAchievement*` |
| Repository | `ResumeAchievementRepository` |

---

## Completion

Rebalanced in Sprint 2E.2 (total **100**):

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
| **Achievements** | **5** |
| Publications | 5 |

Complete when ≥1 non-deleted `resume_achievements` row.

---

## Sprint 2E.1.1 completeness fields

Additive migration `043_extend_resume_achievements.sql`:

- `country_id` / `city_id` (FK, nullable; city must belong to country)
- `award_level` (local → international)
- `rank_or_placement`, `remarks`
- Certificate metadata: `certificate_original_name`, `certificate_mime`, `certificate_size`
- Soft delete retains certificate files; explicit remove deletes storage
- Cities AJAX: `GET .../achievements/cities?country_id=` (resume access required)

Public projection exposes `has_certificate` only — never path/mime/size/original name.

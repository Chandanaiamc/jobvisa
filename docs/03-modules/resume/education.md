# Resume Education (Sprint 2D.4)

Multi-record Education section of the Enterprise Resume Builder.

---

## Schema audit & source of truth

### Existing table (reused — no duplicate system)

`education` was created in `014_create_education_table.sql` and extended in `032` (`school`, `grade`). Ownership is via `resume_id` → `resumes.user_id` (no `user_id` on the row).

Sprint 2C Job Seeker Education (`/jobseeker/education`) already writes to this table through `ensurePrimary()` (primary resume only).

**Source of truth:** the shared `education` table. Resume Builder and Profile Education both use it. A second `resume_education` table was **not** introduced.

### Additive migration `036`

| Column | Purpose |
|---|---|
| `qualification_type` | Enum-like string (bachelor, master, …) |
| `country_id` | FK → `countries` |
| `city` | Free-text city |
| `status` | `active` / `archived` |
| `deleted_at` | Soft delete (restore supported) |

Existing columns mapped in UI:

| Product field | Column |
|---|---|
| Institution | `institution` (+ optional `school`) |
| Qualification title | `degree` |
| Display order | `sort_order` |
| Currently studying | `is_current` |

---

## Routes

Middleware: existing jobseeker group (`web`, `remember`, `auth.web`, `verified`, `jobseeker`, `csrf`).

| Method | Path | Action |
|---|---|---|
| GET | `/jobseeker/resumes/{id}/education` | `index` |
| POST | `/jobseeker/resumes/{id}/education` | `store` |
| GET | `/jobseeker/resumes/{id}/education/{education}/edit` | `edit` |
| POST | `/jobseeker/resumes/{id}/education/{education}` | `update` |
| POST | `/jobseeker/resumes/{id}/education/{education}/delete` | `destroy` (soft) |
| POST | `/jobseeker/resumes/{id}/education/{education}/restore` | `restore` |
| POST | `/jobseeker/resumes/{id}/education/reorder` | `reorder` |

Sprint 2C `/jobseeker/education*` routes remain for primary-resume convenience.

Auth routes unchanged.

---

## Components

| Layer | Class |
|---|---|
| Controller | `App\Controllers\JobSeeker\ResumeEducationController` |
| Service | `Domain\Resume\Services\ResumeEducationService` |
| DTO | `Domain\Resume\DTO\ResumeEducationDTO` |
| Validator | `Domain\Resume\Validators\ResumeEducationValidator` |
| Policy | `Domain\Resume\Policies\ResumeEducationPolicy` (wraps `ResumePolicy`) |
| Repository | `Repositories\EducationRepository` (extended; same interface) |
| Completion | `Domain\Resume\Support\ResumeCompletionCalculator` |

---

## Repository methods

- `listByResumeId` — active rows (`deleted_at IS NULL`), join country name, order by `sort_order`, `start_date DESC`
- `listDeletedByResumeId` — trash
- `findOwned` / `findDeletedOwned`
- `create` / `update`
- `delete` — soft delete + `status=archived`
- `restore`
- `clearCurrentExcept` — only one `is_current` per resume
- `reorder`
- `countryExists`

---

## Validation rules

- Institution required (max 200)
- Qualification title (`degree`) required (max 150)
- Qualification type required (allow-list)
- Start date required (Y-m-d)
- End date required unless currently studying; cannot precede start
- Country must exist when provided
- Grade max 64; safe character set
- Description max 5000
- Status `active`|`archived`
- Display order 0–9999

Field-level errors; form values preserved on failure.

---

## Authorization

| Actor | Access |
|---|---|
| Owner seeker | View + create/update/delete/restore/reorder |
| Admin / staff | View (read-only UI) |
| Employer | Denied |
| Other seeker | Denied |

CSRF on every POST. Prepared statements. Output escaped via `e()`.

---

## Completion

`WEIGHT_EDUCATION = 17`. Complete when at least one **non-deleted** education row exists for the resume (`deleted_at IS NULL`). Recalculated after every mutate via `ResumeCompletionCalculator`.

---

## Migration & rollback

```bash
Get-Content database/migrations/036_extend_education_resume_builder.sql -Raw | E:\localhost\mysql\bin\mysql.exe -u root jobvisa_db
# Rollback:
Get-Content database/migrations/036_extend_education_resume_builder_rollback.sql -Raw | E:\localhost\mysql\bin\mysql.exe -u root jobvisa_db
```

Rollback drops only 036 columns/indexes/FK. Rows and earlier columns remain.

---

## Dual-path note

- Resume Builder: any resume the seeker owns
- `/jobseeker/education`: still primary resume via `ensurePrimary`
- Soft delete is shared — Profile Education list hides trashed rows

---

## Manual testing

1. Open `/jobseeker/resumes/{id}/education` as owner
2. Add two qualifications → both listed; completion includes education weight
3. Mark one currently studying → end date cleared; other currents cleared
4. Edit / reorder ↑↓ / soft-delete → trash → restore
5. Invalid dates / missing institution → field errors preserved
6. Admin view read-only; other seeker / employer forbidden
7. Profile `/jobseeker/education`, Personal, Professional, Resume CRUD, CV still work
8. Login/register unchanged

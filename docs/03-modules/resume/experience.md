# Resume Work Experience (Sprint 2D.5)

Multi-record Work Experience section of the Enterprise Resume Builder.

---

## Schema audit & source of truth

### Existing table (reused — no duplicate system)

`work_experience` was created in `015_create_work_experience_table.sql` with:

`resume_id`, `company_name`, `job_title`, `country_id`, `start_date`, `end_date`, `is_current`, `description`, `sort_order`, timestamps.

Sprint 2C Job Seeker Experience (`/jobseeker/experience`) already writes here via `ensurePrimary()` (primary resume only).

**Source of truth:** shared `work_experience` table. No second experience table was created.

### Additive migration `037`

| Change | Purpose |
|---|---|
| `employment_type` | Full-time, contract, … |
| `industry` | Optional industry |
| `city` | Free-text city |
| `responsibilities` | Role duties (backfilled from `description`) |
| `achievements` | Highlights |
| `reason_for_leaving` | **Private** |
| `supervisor_name` | Optional |
| `supervisor_contact` | **Private** |
| `status` | `active` / `archived` |
| `deleted_at` | Soft delete |
| `work_experience_skills` | Pivot to master `skills` (not `user_skills`) |

`description` remains synced from responsibilities so Sprint 2C UI keeps working.

---

## Routes

Middleware: jobseeker group (`web`, `remember`, `auth.web`, `verified`, `jobseeker`, `csrf`).

| Method | Path | Action |
|---|---|---|
| GET | `/jobseeker/resumes/{id}/experience` | `index` |
| POST | `/jobseeker/resumes/{id}/experience` | `store` |
| GET | `/jobseeker/resumes/{id}/experience/{experience}/edit` | `edit` |
| POST | `/jobseeker/resumes/{id}/experience/{experience}` | `update` |
| POST | `/jobseeker/resumes/{id}/experience/{experience}/delete` | soft delete |
| POST | `/jobseeker/resumes/{id}/experience/{experience}/restore` | `restore` |
| POST | `/jobseeker/resumes/{id}/experience/reorder` | `reorder` |

Sprint 2C `/jobseeker/experience*` unchanged. Auth unchanged.

---

## Components

| Layer | Class |
|---|---|
| Controller | `App\Controllers\JobSeeker\ResumeExperienceController` |
| Service | `Domain\Resume\Services\ResumeExperienceService` |
| DTO | `Domain\Resume\DTO\ResumeExperienceDTO` |
| Validator | `Domain\Resume\Validators\ResumeExperienceValidator` |
| Policy | `Domain\Resume\Policies\ResumeExperiencePolicy` |
| Repository | `Repositories\WorkExperienceRepository` (extended) |
| Completion | `ResumeCompletionCalculator` (`WEIGHT_EXPERIENCE` = 18) |

---

## Repository methods

- `listByResumeId` / `listDeletedByResumeId`
- `findOwned` / `findDeletedOwned`
- `create` / `update` / soft `delete` / `restore` / `reorder`
- `countryExists`
- `listSkillIds` / `syncSkills` / `mapSkillsForExperiences` / `filterActiveSkillIds`

---

## Validation

- Company + job title required
- Employment type allow-list
- Country required and must exist
- Start date required; end date required unless currently working; end ≥ start
- Responsibilities / achievements max 8000
- Private fields length-capped
- Skill IDs must exist in active `skills` catalogue

---

## Private vs public fields

| Field | Public resume / employer | Owner / admin |
|---|---|---|
| reason_for_leaving | Hidden (`toPublicArray`) | Visible when authorized |
| supervisor_contact | Hidden | Visible when authorized |
| supervisor_name | Allowed | Allowed |

---

## Skills used

- Catalogue: `skills`
- Pivot: `work_experience_skills`
- Does **not** modify `user_skills` profile data

---

## Currently working

Multiple concurrent `is_current` roles are allowed (unlike education’s single-current rule).

---

## Completion

Active (non-deleted) experience rows earn `WEIGHT_EXPERIENCE` (18). Total weights remain 100.

---

## Migration & rollback

```bash
Get-Content database/migrations/037_extend_work_experience_resume_builder.sql -Raw | E:\localhost\mysql\bin\mysql.exe -u root jobvisa_db
Get-Content database/migrations/037_extend_work_experience_resume_builder_rollback.sql -Raw | E:\localhost\mysql\bin\mysql.exe -u root jobvisa_db
```

---

## Manual testing

1. Add two roles; mark both currently working → both show Current
2. Link skills from multi-select → cards show skills
3. Edit / reorder ↑↓ / soft-delete → restore
4. Private fields visible to owner; `toPublicArray` omits them
5. Invalid dates / country → field errors preserved
6. Profile Experience, Education, Personal, Professional, CV, Resume CRUD still work
7. Auth unchanged

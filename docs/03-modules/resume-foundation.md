# Enterprise Resume Foundation (Sprint 2D.1)

Multi-resume foundation for JobVisa.lk — Domain-driven, compatible with Sprint 2C CV/profile flows.

---

## Components

| Layer | Class |
|---|---|
| Entity | `Domain\Resume\Entities\Resume` |
| Aggregate | `Domain\Resume\Aggregates\ResumeAggregate` |
| Repository interface | `Domain\Resume\Repositories\ResumeRepositoryInterface` |
| Repository | `Repositories\ResumeRepository` (also implements infra CV contract) |
| Service | `Domain\Resume\Services\ResumeService` |
| Controller | `Controllers\JobSeeker\ResumeController` |
| DTO | `Domain\Resume\DTO\ResumeData` |
| Validator | `Domain\Resume\Validators\ResumeValidator` |
| Policy | `Domain\Resume\Policies\ResumePolicy` |
| Factory | `Domain\Resume\Factories\ResumeFactory` |
| Routes | Additive under `/jobseeker/resumes*` |
| Dashboard | `views/jobseeker/pages/resumes/*` |

---

## Features

- Multiple resumes per seeker
- Status: `draft` | `published`
- Default resume (`is_primary`)
- Soft delete (`deleted_at`)
- Visibility: `public` | `employers` | `private`
- Completion percentage (`completeness_score`)
- `created_at` / `updated_at`

---

## Migration

`033_extend_resumes_foundation.sql` — adds `status`, `visibility`, `deleted_at` only. Does not modify migration `013`.

---

## Routes (additive)

| Method | Path |
|---|---|
| GET | `/jobseeker/resumes` |
| GET | `/jobseeker/resumes/create` |
| POST | `/jobseeker/resumes` |
| GET | `/jobseeker/resumes/{id}` |
| GET | `/jobseeker/resumes/{id}/edit` |
| POST | `/jobseeker/resumes/{id}` |
| POST | `/jobseeker/resumes/{id}/publish` |
| POST | `/jobseeker/resumes/{id}/draft` |
| POST | `/jobseeker/resumes/{id}/default` |
| POST | `/jobseeker/resumes/{id}/delete` |

Existing `/jobseeker/cv*` and auth routes are unchanged.

---

## Security

- `ResumePolicy`: owner seeker can CRUD/publish/default; admin may view; employer denied
- Soft-deleted resumes excluded from queries
- CSRF on POST
- Auth middleware stack unchanged

---

## Compatibility

- `ensurePrimary()`, `updateFile()`, `updateCompleteness()` preserved for Sprint 2C
- Education / experience / CV still attach to primary resume
- Authentication providers untouched

# Resume Professional Summary (Sprint 2D.3)

Headline & professional summary section of the Enterprise Resume Builder. Stores resume-specific career details in `resume_professional` and syncs shared headline / summary / expected salary back to `user_profiles`.

---

## Data source strategy

| Field | Source of truth | Notes |
|---|---|---|
| Professional headline | `resume_professional.headline` | Prefills from `user_profiles.headline`; synced on save |
| Professional summary | `resume_professional.summary` | Prefills from profile; synced on save |
| Career objective | `resume_professional.career_objective` | Resume-specific |
| Years of experience | `resume_professional.years_of_experience` | Resume-specific |
| Current job title / company / industry | `resume_professional` | Resume-specific |
| Current salary | `resume_professional.current_salary` | Optional |
| Expected salary | `resume_professional.expected_salary` | Prefills from profile; synced on save |
| Preferred currency | `resume_professional.preferred_currency` | Resume-specific (LKR default) |
| Notice period | `resume_professional.notice_period` | Resume-specific enum |
| Employment status | `resume_professional.employment_status` | Required |
| Open to relocate / remote | `resume_professional` | Boolean flags |

---

## Migration

- Forward: `database/migrations/035_create_resume_professional.sql`
- Rollback: `database/migrations/035_create_resume_professional_rollback.sql`

```bash
Get-Content database/migrations/035_create_resume_professional.sql -Raw | E:\localhost\mysql\bin\mysql.exe -u root jobvisa_db
# Rollback:
Get-Content database/migrations/035_create_resume_professional_rollback.sql -Raw | E:\localhost\mysql\bin\mysql.exe -u root jobvisa_db
```

---

## Routes

Middleware: existing jobseeker group (`web`, `remember`, `auth.web`, `verified`, `jobseeker`, `csrf`).

| Method | Path | Action |
|---|---|---|
| GET | `/jobseeker/resumes/{id}/professional` | `ResumeProfessionalController@edit` |
| POST | `/jobseeker/resumes/{id}/professional` | `ResumeProfessionalController@update` |
| POST | `/jobseeker/resumes/{id}/professional/autosave` | `ResumeProfessionalController@autosave` |

Auth and unrelated routes unchanged.

---

## Components

| Layer | Class |
|---|---|
| Controller | `App\Controllers\JobSeeker\ResumeProfessionalController` |
| Service | `Domain\Resume\Services\ResumeProfessionalService` |
| DTO | `Domain\Resume\DTO\ResumeProfessionalDTO` |
| Validator | `Domain\Resume\Validators\ResumeProfessionalValidator` |
| Repository | `Repositories\ResumeProfessionalRepository` |
| Completion | `Domain\Resume\Support\ResumeCompletionCalculator` |

---

## Validation rules

- Headline required, max 255
- Summary required, 40–5000 characters
- Career objective optional, max 2000
- Years of experience 0–60 when provided
- Job title / company / industry length caps
- Expected salary required (non-negative); current salary optional
- Currency / notice period / employment status from allow-lists
- Employment status required on full save
- Autosave is lenient on “required” / short-summary messages; still blocks format errors

---

## Completion

`ResumeCompletionCalculator` weights (total 100):

- Title 8 · Personal 25 · Professional 20 · CV file 12 · Education 17 · Experience 18

Professional complete when: headline, summary (≥40 chars), employment status, expected salary, and (years of experience **or** current job title) are present.

---

## Autosave

Client debounce (~900ms) posts FormData to `/professional/autosave`. Response returns updated completion score and rotated CSRF token.

---

## Authorization

- Owner seeker: edit + autosave
- Admin/staff: view (read-only UI)
- Employer: denied

---

## Manual testing

1. Open `/jobseeker/resumes/{id}/professional` as seeker
2. Fill headline, summary (≥40), employment status → Save → flash success
3. Type further fields → autosave status updates; completion bar moves
4. Invalid currency / salary → field errors preserved
5. Confirm profile headline/summary/expected salary sync
6. Subnav shows Professional between Personal and Settings
7. Resume foundation + personal + CV still work
8. Login/register unchanged

---

## Rollback instructions

Run `035_create_resume_professional_rollback.sql`. Resume foundation and personal tables remain intact.

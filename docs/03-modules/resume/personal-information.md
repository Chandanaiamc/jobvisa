# Resume Personal Information (Sprint 2D.2)

Personal Information section of the Resume Builder. Reuses `user_profiles` / `users` as the primary source of truth and stores only resume-specific overrides.

---

## Data source strategy

| Field | Source of truth | Notes |
|---|---|---|
| Profile photo | `user_profiles.avatar_path` | Shared; not duplicated per resume |
| First / last name | `user_profiles` (+ `users.full_name`) | Updated from resume personal form |
| Headline / summary | `user_profiles` | Shared |
| DOB, gender, marital status | `user_profiles` | Shared |
| NIC number | `user_profiles.nic_passport` | Column stores NIC (legacy name retained) |
| Passport number / expiry | `resume_personal` | Resume-specific override |
| Current country / city / address | `user_profiles` | Shared |
| Phone / WhatsApp | `users.phone` / `user_profiles.whatsapp` | Shared |
| Email | `users.email` | **Read-only** on this screen |
| Expected salary | `user_profiles.expected_salary` | Shared amount |
| Salary currency | `resume_personal.salary_currency` | Resume-specific |
| Preferred job countries | `resume_preferred_countries` | Multi-select; first also syncs `user_profiles.preferred_country_id` |
| Visa status | `resume_personal.visa_status` | Resume-specific |
| Driving licence status | `resume_personal.driving_licence_status` | Resume-specific |

---

## Migration

- Forward: `database/migrations/034_create_resume_personal.sql`
- Rollback: `database/migrations/034_create_resume_personal_rollback.sql`

```bash
Get-Content database/migrations/034_create_resume_personal.sql -Raw | E:\localhost\mysql\bin\mysql.exe -u root jobvisa_db
# Rollback:
Get-Content database/migrations/034_create_resume_personal_rollback.sql -Raw | E:\localhost\mysql\bin\mysql.exe -u root jobvisa_db
```

---

## Routes

Middleware: existing jobseeker group (`web`, `remember`, `auth.web`, `verified`, `jobseeker`, `csrf`).

| Method | Path | Action |
|---|---|---|
| GET | `/jobseeker/resumes/{id}/personal` | `ResumePersonalController@edit` |
| POST | `/jobseeker/resumes/{id}/personal` | `ResumePersonalController@update` |
| POST | `/jobseeker/resumes/{id}/photo` | `ResumePersonalController@uploadPhoto` |
| POST | `/jobseeker/resumes/{id}/photo/delete` | `ResumePersonalController@deletePhoto` |

Public routes and CV upload routes are unchanged.

---

## Components

| Layer | Class |
|---|---|
| Controller | `App\Controllers\JobSeeker\ResumePersonalController` |
| Service | `Domain\Resume\Services\ResumePersonalService` |
| DTO | `Domain\Resume\DTO\ResumePersonalDTO` |
| Validator | `Domain\Resume\Validators\ResumePersonalValidator` |
| Repository | `Repositories\ResumePersonalRepository` |
| Completion | `Domain\Resume\Support\ResumeCompletionCalculator` |

---

## Validation rules

- First / last name required (max 80)
- Optional dates must be `Y-m-d`
- Passport expiry cannot be before DOB
- Phone / WhatsApp max 32
- Salary numeric and ≥ 0
- Currency in `LKR,USD,AED,QAR,SAR,EUR,GBP`
- Gender / marital / visa / licence enums
- Country IDs must exist and be active
- Email not accepted for update

---

## Upload security

- MIME checked via `finfo` (JPEG/PNG/WebP)
- Max 3 MB (`config/uploads.php`)
- Random safe filename under `storage/uploads/avatars/{userId}/`
- Path traversal stripped; directory outside web-executable roots
- Old file deleted on replace/delete
- Owner-only via `ResumePolicy::update`

---

## Authorization

- Owner seeker: edit
- Admin/staff: view (read-only UI)
- Employer: denied

---

## Completion calculation

`ResumeCompletionCalculator` weights (total 100):

- Title 8 · Personal 15 · Professional 12 · CV file 7 · Education 9 · Experience 9 · Skills 5 · Languages 5 · Certifications 5 · Projects 7 · Achievements 5 · Publications 5 · Portfolio 4 · References 4

Personal complete when: first name, last name, DOB, gender, nationality, phone, headline are present.

(Module docs under `docs/03-modules/resume/`.)

---

## Manual testing

1. Open `/jobseeker/resumes/{id}/personal` as seeker
2. Save identity + contact fields → profile updated; email unchanged
3. Set passport / visa / licence / multi preferred countries → `resume_personal` + pivot
4. Upload photo ≤ 3MB → appears; replace; delete
5. Invalid salary / currency → field errors, values preserved
6. Employer / other seeker → forbidden
7. Profile page still shows shared fields
8. Resume CRUD + CV upload still work
9. Login/register unchanged

---

## Rollback instructions

Run `034_create_resume_personal_rollback.sql`. Profile columns and resume foundation remain intact.

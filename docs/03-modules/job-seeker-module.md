# Job Seeker Profile Module (Sprint 2C)

Complete profile management for authenticated, email-verified seekers.

---

## Architecture

| Layer | Components |
|---|---|
| Routes | `routes/jobseeker.php`, admin view in `routes/admin.php` |
| Controllers | `App\Controllers\JobSeeker\*` |
| Services | `JobVisa\App\JobSeeker\*` |
| Repositories | `UserProfile`, `Resume`, `Education`, `WorkExperience`, skills/languages/location catalogs |
| Provider | `JobSeekerServiceProvider` (auth unchanged) |
| UI | `app/views/jobseeker/*`, `public/assets/css/jobseeker.css` |
| Schema | Existing tables `009`/`013`–`017` + new migration `032_extend_job_seeker_profile` |

---

## Routes

### Seeker (middleware: `web`, `remember`, `auth.web`, `verified`, `jobseeker`, `csrf`)

| Method | Path | Controller |
|---|---|---|
| GET | `/jobseeker` | `DashboardController@index` |
| GET/POST | `/jobseeker/profile` | `ProfileController@edit/update` |
| POST | `/jobseeker/profile/avatar` | `ProfileController@uploadAvatar` |
| GET | `/jobseeker/media/avatar` | `MediaController@avatar` |
| GET/POST | `/jobseeker/education` | Education CRUD |
| POST | `/jobseeker/education/{id}` | update |
| POST | `/jobseeker/education/{id}/delete` | delete |
| GET/POST | `/jobseeker/experience` | Experience CRUD |
| POST | `/jobseeker/experience/{id}` / `.../delete` | update/delete |
| GET/POST | `/jobseeker/skills` | Skills |
| POST | `/jobseeker/skills/{id}/delete` | delete |
| GET/POST | `/jobseeker/languages` | Languages |
| POST | `/jobseeker/languages/{id}` / `.../delete` | update/delete |
| GET/POST | `/jobseeker/cv` | CV upload |
| GET | `/jobseeker/cv/download` | download |
| POST | `/jobseeker/cv/delete` | delete |
| GET | `/jobseeker/settings` | settings |

### Admin (read-only)

| Method | Path | Controller |
|---|---|---|
| GET | `/admin/seekers/{id}` | `Admin\SeekerProfileController@show` |

---

## Controllers

- `JobSeekerController` — shared actor helpers + dashboard layout renderer  
- `DashboardController`, `ProfileController`, `EducationController`, `ExperienceController`, `SkillController`, `LanguageController`, `CvController`, `SettingsController`, `MediaController`  
- `Admin\SeekerProfileController` — view only  

---

## Repositories

| Repository | Table(s) |
|---|---|
| `UserProfileRepository` | `user_profiles` + user/country/city joins |
| `ResumeRepository` | `resumes` (primary CV + completeness) |
| `EducationRepository` | `education` |
| `WorkExperienceRepository` | `work_experience` |
| `SkillCatalogRepository` / `UserSkillRepository` | `skills` / `user_skills` |
| `LanguageCatalogRepository` / `UserLanguageRepository` | `languages` / `user_languages` |
| `LocationRepository` | `countries`, `cities` |

---

## Services

| Service | Role |
|---|---|
| `ProfileAccess` | Owner edit; admin/staff view; employer cannot edit |
| `ProfileService` | Personal fields + avatar |
| `EducationService` / `ExperienceService` | Multi-record CRUD on primary resume |
| `SkillService` | Master + custom skills + level |
| `LanguageService` | Speaking / reading / writing |
| `CvService` | Upload / replace / download / delete PDF |
| `ProfileCompletenessService` | Weighted % + persist `resumes.completeness_score` |

### Completeness weights

Personal 25 · Headline/summary 10 · Photo 5 · Contact 10 · Education 15 · Experience 15 · Skills 10 · Languages 5 · CV 5

---

## Security

- Seeker middleware + verified email on all `/jobseeker/*` routes  
- `ProfileAccess::canEdit` — owner seeker only  
- `ProfileAccess::canView` — owner or admin/staff  
- Employers cannot edit (no write routes; access denied in services)  
- CSRF on mutating requests  
- Prepared statements via repositories  
- Uploads: MIME + size checks; files under `storage/uploads` (web-denied)  
- CV/avatar streamed through authenticated controllers  
- Output escaped with `e()`  
- Email field read-only  

---

## Views

Sidebar dashboard: Overview, Profile, Education, Experience, Skills, Languages, CV, Settings + progress bar.

---

## Migration

Apply:

```bash
E:\localhost\mysql\bin\mysql.exe -u root jobvisa_db < database/migrations/032_extend_job_seeker_profile.sql
```

Adds: profile first/last name, NIC, marital status, salary, current country, address, WhatsApp; education school/grade; language speaking/reading/writing.

---

## Manual testing

1. Login as verified seeker → `/jobseeker` overview + progress bar  
2. Save personal profile fields; email remains read-only  
3. Upload avatar; appears on profile  
4. Add/edit/delete education & experience  
5. Add catalog + custom skill with level  
6. Add language with speaking/reading/writing  
7. Upload PDF CV; download; replace; delete  
8. Completeness % increases as sections fill  
9. Admin opens `/admin/seekers/{id}` — read-only  
10. Employer cannot access `/jobseeker/*` (403)  

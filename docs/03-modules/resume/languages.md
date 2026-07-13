# Resume Languages (Sprint 2D.7)

Resume-scoped language proficiency for the Enterprise Resume Builder.

---

## Architecture audit

| Concern | Finding |
|---|---|
| Catalogue | `languages` (`006_create_languages_table`) |
| Profile languages | `user_languages` — `user_id` scoped (Sprint 2C). **Not used** here |
| Prior resume languages | None — new `resume_languages` |

**Source of truth:** catalogue `languages` + resume links in `resume_languages`. Profile module unchanged.

---

## Migration

- `039_create_resume_languages.sql` / rollback
- Fields: speaking/reading/writing/listening (CEFR), `is_native`, certificate type/score/dates/path, sort order, status, soft delete

---

## Routes

| Method | Path |
|---|---|
| GET | `/jobseeker/resumes/{id}/languages` |
| GET | `/jobseeker/resumes/{id}/languages/search` |
| POST | `/jobseeker/resumes/{id}/languages` |
| POST | `/jobseeker/resumes/{id}/languages/reorder` |
| GET/POST | `/jobseeker/resumes/{id}/languages/{language}/edit` / update |
| POST | `.../delete` · `.../restore` |
| POST | `.../certificate` · `.../certificate/delete` |
| GET | `.../certificate/download` |

CSRF on POSTs. Auth unchanged. Owner-only manage via `ResumeLanguagePolicy`.

---

## Components

| Layer | Class |
|---|---|
| Controller | `ResumeLanguageController` |
| Service | `ResumeLanguageService` |
| DTO / Validator / Policy | `ResumeLanguage*` |
| Repository | `ResumeLanguageRepository` |
| Catalogue | `LanguageCatalogRepository::search` / `isActive` |

---

## CEFR & certificates

- Levels: A1–C2 for speaking, reading, writing, listening
- Native toggle (defaults CEFR to C2 when marked native on save if empty)
- Certificates: IELTS, TOEFL, PTE, HSK, JLPT, TOPIK, Other
- Upload: PDF/JPEG/PNG ≤ 5MB via `FileStorage` → `language-certs/{userId}/{resumeId}`

---

## Completion

Rebalanced weights (total **100** — see also `projects.md` / `certifications.md`):

| Section | Weight |
|---|---|
| Title | 8 |
| Personal | 20 |
| Professional | 15 |
| CV | 8 |
| Education | 12 |
| Experience | 12 |
| Skills | 7 |
| **Languages** | **5** |
| Certifications | 5 |
| Projects | 8

Complete when ≥1 non-deleted `resume_languages` row.

---

## Manual testing

1. Search catalogue → add language with CEFR + native  
2. Edit certificate metadata; upload/download/remove file  
3. Soft-delete → restore; reorder  
4. Completion includes languages weight  
5. `/jobseeker/languages` profile module still works  
6. Prior resume sections intact; auth unchanged  

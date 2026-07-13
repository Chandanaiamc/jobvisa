# Resume Certifications & Licences (Sprint 2D.8)

Resume-scoped certifications and professional licences for the Enterprise Resume Builder.

---

## Architecture audit

| Concern | Finding |
|---|---|
| Prior resume certifications | None — new `resume_certifications` |
| Profile licence | `driving_licence_status` on personal / profile — **unchanged** |
| Language certificates | `resume_languages.certificate_*` — separate concern |

**Source of truth:** `resume_certifications` keyed by `resume_id`. Not linked to user profile tables.

---

## Migration

- `040_create_resume_certifications.sql` / rollback
- Fields: name, issuing organization, credential ID/URL, issue/expiry, does-not-expire, license number, verification URL, certificate path, primary, sort order, status, soft delete

---

## Routes

| Method | Path |
|---|---|
| GET | `/jobseeker/resumes/{id}/certifications` |
| POST | `/jobseeker/resumes/{id}/certifications` |
| POST | `/jobseeker/resumes/{id}/certifications/reorder` |
| GET/POST | `/jobseeker/resumes/{id}/certifications/{certification}/edit` / update |
| POST | `.../delete` · `.../restore` |
| POST | `.../certificate` · `.../certificate/delete` |
| GET | `.../certificate/download` |

CSRF on POSTs. Auth unchanged. Owner-only manage via `ResumeCertificationPolicy`.

---

## Components

| Layer | Class |
|---|---|
| Controller | `ResumeCertificationController` |
| Service | `ResumeCertificationService` |
| DTO / Validator / Policy | `ResumeCertification*` |
| Repository | `ResumeCertificationRepository` |

---

## Validation & files

- Required: name, issuing organization, issue date
- Expiry required unless `does_not_expire`
- Expiry ≥ issue date; credential/verification URLs validated
- Upload: PDF/JPEG/PNG ≤ 5MB via `FileStorage` → `resume-certs/{userId}/{resumeId}`

---

## Completion

Rebalanced weights (total **100** — see `projects.md` for Sprint 2D.9):

| Section | Weight |
|---|---|
| Title | 8 |
| Personal | 20 |
| Professional | 15 |
| CV | 8 |
| Education | 12 |
| Experience | 12 |
| Skills | 7 |
| Languages | 5 |
| Certifications | 5 |
| **Projects** | **8** |

Complete when ≥1 non-deleted `resume_certifications` row.

---

## Manual testing

1. Add certification with org + issue date + expiry  
2. Toggle does-not-expire → expiry cleared/disabled  
3. Mark primary; upload/download/remove certificate file  
4. Soft-delete → restore; reorder  
5. Completion includes certifications weight  
6. Prior resume sections + profile modules intact; auth unchanged  

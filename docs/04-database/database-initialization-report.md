# Database Initialization Report — Sprint 1.1

**Project:** JobVisa.lk  
**Date:** 2026-07-11  
**Database:** `jobvisa_db`  
**Status:** **PASS**

---

## Summary

| Step | Result |
|---|---|
| Schema vs migrations audit | Empty DB prior to run; **27** forward migrations pending |
| Apply migrations `001`–`022`, `027`–`031` | **PASS** (0 failures) |
| Archive migrations `023`–`026` | **Not applied** (correct — archived drafts) |
| Enterprise seeder run | **PASS** (13 seeders; re-run idempotent) |
| Required tables present | **26 / 26** |
| Reference + admin seed data | **PASS** |
| Sprint 1 auth tables | **PASS** |
| Read-only post-check (HTTP + auth flow) | **PASS** |

No application business logic, routes, or controllers were modified during this sprint.

---

## Pre-state

| Check | Value |
|---|---|
| Database reachable | Yes (`localhost`, user `root`) |
| Tables before init | **0** |
| Pending forward migrations | `001`–`022`, `027`–`031` |

---

## Migrations applied (order)

| # | File | Result |
|---|---|---|
| 001 | `001_create_countries_table.sql` | OK |
| 002 | `002_create_cities_table.sql` | OK |
| 003 | `003_create_job_categories_table.sql` | OK |
| 004 | `004_create_job_types_table.sql` | OK |
| 005 | `005_create_skills_table.sql` | OK |
| 006 | `006_create_languages_table.sql` | OK |
| 007 | `007_create_subscription_plans_table.sql` | OK |
| 008 | `008_create_users_table.sql` | OK |
| 009 | `009_create_user_profiles_table.sql` | OK |
| 010 | `010_create_companies_table.sql` | OK |
| 011 | `011_create_employers_table.sql` | OK |
| 012 | `012_create_jobs_table.sql` | OK |
| 013 | `013_create_resumes_table.sql` | OK |
| 014 | `014_create_education_table.sql` | OK |
| 015 | `015_create_work_experience_table.sql` | OK |
| 016 | `016_create_user_skills_table.sql` | OK |
| 017 | `017_create_user_languages_table.sql` | OK |
| 018 | `018_create_applications_table.sql` | OK |
| 019 | `019_create_saved_jobs_table.sql` | OK |
| 020 | `020_create_notifications_table.sql` | OK |
| 021 | `021_create_payments_table.sql` | OK |
| 022 | `022_create_audit_logs_table.sql` | OK |
| 027 | `027_create_roles_table.sql` | OK |
| 028 | `028_alter_users_auth_foundation.sql` | OK |
| 029 | `029_create_password_reset_tokens_table.sql` | OK |
| 030 | `030_create_email_verification_tokens_table.sql` | OK |
| 031 | `031_create_login_attempts_table.sql` | OK |

**Skipped (by design):** `database/migrations/archive/023`–`026` and all `*_rollback.sql` files.

---

## Tables verified (26)

`countries`, `cities`, `job_categories`, `job_types`, `skills`, `languages`, `subscription_plans`, `users`, `user_profiles`, `companies`, `employers`, `jobs`, `resumes`, `education`, `work_experience`, `user_skills`, `user_languages`, `applications`, `saved_jobs`, `notifications`, `payments`, `audit_logs`, `roles`, `password_reset_tokens`, `email_verification_tokens`, `login_attempts`

### Auth columns on `users` (Sprint 1)

| Column | Present |
|---|---|
| `password_hash` | Yes |
| `role` | Yes |
| `role_id` | Yes |
| `remember_token` | Yes |
| `deleted_at` | Yes |
| `email_verified_at` | Yes |
| `status` | Yes |

### Sprint 1 auth tables

| Table | Present |
|---|---|
| `roles` | Yes |
| `password_reset_tokens` | Yes |
| `email_verification_tokens` | Yes |
| `login_attempts` | Yes |

---

## Seeder results

Command: `php database/seed.php` (executed twice — idempotent)

| Seeder | Result | Notes |
|---|---|---|
| Roles | OK | 4 system roles |
| Permissions | OK | No-op (no `permissions` table yet) |
| Countries | OK | 12 |
| Cities | OK | 20 |
| Job Categories | OK | 10 |
| Job Types | OK | 5 |
| Skills | OK | 15 |
| Languages | OK | 8 |
| Subscription Plans | OK | 5 |
| Default Admin | OK | `admin@jobvisa.lk` |
| Demo Employer | OK | demo account + company |
| Demo Job Seeker | OK | demo account + profile |
| Demo Jobs | OK | 3 published demo jobs |

### Seed counts (post-run)

| Dataset | Count | Expected |
|---|---|---|
| Roles (`admin`, `employer`, `seeker`, `staff`) | 4 | ≥ 4 |
| Countries | 12 | ≥ 1 |
| Cities | 20 | ≥ 1 |
| Job categories | 10 | ≥ 1 |
| Job types | 5 | ≥ 1 |
| Skills | 15 | ≥ 1 |
| Languages | 8 | ≥ 1 |
| Admin user | 1 | 1 |
| Admin linked to `roles.slug=admin` | 1 | 1 |
| Duplicate role slugs | 0 | 0 |

Default admin password remains the configured local placeholder (`SEED_ADMIN_PASSWORD` / `ChangeMeAdmin!123`). Change before any shared environment.

---

## Read-only verification (post-init)

| Check | Result |
|---|---|
| `GET /` | HTTP 200 |
| `GET /about` | HTTP 200 |
| `GET /health/database` | HTTP 200 |
| `GET /auth/csrf` | HTTP 200 |
| Register → verify email → login (new seeker) | PASS |
| Admin login (`admin@jobvisa.lk`) | PASS |
| Business logic / routes / controllers unchanged | Confirmed (ops-only sprint) |

---

## Final status

**PASS**

---

*End of database initialization report.*

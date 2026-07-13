# Database Seeder Architecture

**Project:** JobVisa.lk  
**Status:** Active — additive; migrations untouched  

---

## Overview

The enterprise seeder system loads **reference and demo data** into tables created by migrations `001`–`031`. Seeders are PHP classes under `JobVisa\App\Database\Seeders`, registered in `config/seeders.php`, and executed via:

```bash
php database/seed.php
```

Optional filter:

```bash
php database/seed.php --only=Roles
php database/seed.php --only=JobVisa\App\Database\Seeders\CountrySeeder
```

Design goals:

- Compatible with existing schema and FK order  
- Modular (one concern per seeder)  
- Idempotent (safe to re-run)  
- No changes to migrations or application business logic  

---

## Architecture

```text
database/seed.php          CLI entry (bootstrap + run)
config/seeders.php         Ordered class list + demo credentials
app/Database/Seeders/
  Contracts/SeederInterface.php
  Support/Seeder.php           Shared idempotent helpers
  Support/SeederRunner.php     Transactional orchestration
  DatabaseSeeder.php           Optional full-stack orchestrator
  RoleSeeder.php
  PermissionSeeder.php
  CountrySeeder.php
  CitySeeder.php
  JobCategorySeeder.php
  JobTypeSeeder.php
  SkillSeeder.php
  LanguageSeeder.php
  SubscriptionPlanSeeder.php
  DefaultAdminSeeder.php
  DemoEmployerSeeder.php
  DemoJobSeekerSeeder.php
  DemoJobSeeder.php
```

PSR-4: `JobVisa\App\` → `app/` (no Composer mapping change required).

`SeederRunner` wraps each seeder in a DB transaction. Failures roll back that seeder and abort the run.

---

## Execution order

Order in `config/seeders.php` mirrors FK dependencies from the migration timeline:

| # | Seeder | Depends on | Tables touched |
|---|---|---|---|
| 1 | Roles | `027` roles | `roles` |
| 2 | Permissions | roles (+ future permissions tables) | `permissions`, `role_permissions` *(if present)* |
| 3 | Countries | `001` | `countries` |
| 4 | Cities | countries | `cities` |
| 5 | Job Categories | `003` | `job_categories` |
| 6 | Job Types | `004` | `job_types` |
| 7 | Skills | `005` | `skills` |
| 8 | Languages | `006` | `languages` |
| 9 | Subscription Plans | `007` | `subscription_plans` |
| 10 | Default Admin | users, roles | `users` |
| 11 | Demo Employer | users, companies, employers, geo | `users`, `companies`, `employers` |
| 12 | Demo Job Seeker | users, profiles, geo | `users`, `user_profiles` |
| 13 | Demo Jobs | employer + taxonomies | `jobs` |

**Do not reorder** without reviewing foreign keys (`cities.country_id`, `jobs.employer_id`, etc.).

---

## Idempotency strategy

| Data type | Strategy |
|---|---|
| Reference rows (roles, countries, plans, …) | Lookup by unique key (`slug`, `iso2`, `code`); insert if missing; refresh non-key metadata on conflict |
| Demo users | Lookup by **email**; insert only if missing (**passwords are not reset** on re-run) |
| Company / employer / profile / jobs | Lookup by slug or owning `user_id`; insert only if missing |
| Permissions | No-op if `permissions` table does not exist |

Unique keys used for upsert detection:

- `roles.slug`, `countries.iso2`, `cities (country_id, slug)`, `job_categories.slug`, `job_types.slug`, `skills.slug`, `languages.code`, `subscription_plans.code`, `users.email`, `companies.slug`, `jobs.slug`

---

## Permissions note

There is **no** `permissions` / `role_permissions` table in migrations `001`–`031` today.  
`PermissionSeeder` is ready with canonical permission slugs and admin role mapping, but **skips safely** until those tables are added by a future migration. This keeps seeders compatible with the current schema without altering migrations.

---

## Demo credentials

Defaults (override with `.env`):

| Account | Env keys | Default email |
|---|---|---|
| Admin | `SEED_ADMIN_EMAIL`, `SEED_ADMIN_PASSWORD`, `SEED_ADMIN_NAME` | `admin@jobvisa.lk` |
| Employer | `SEED_EMPLOYER_EMAIL`, `SEED_EMPLOYER_PASSWORD`, `SEED_EMPLOYER_NAME` | `employer@demo.jobvisa.lk` |
| Seeker | `SEED_SEEKER_EMAIL`, `SEED_SEEKER_PASSWORD`, `SEED_SEEKER_NAME` | `seeker@demo.jobvisa.lk` |

Default passwords are local-dev placeholders (`ChangeMe…`). **Change them before any shared or production environment.** Re-running seeders will not overwrite an existing user’s password.

---

## Future expansion strategy

1. **Add a seeder class** under `app/Database/Seeders` implementing `SeederInterface` / extending `Seeder`.  
2. **Register** it in `config/seeders.php` `order` at the correct FK position.  
3. Prefer **natural unique keys** over hard-coded numeric IDs.  
4. When `permissions` migrations land, re-run `PermissionSeeder` — no architecture change required.  
5. Split large taxonomies into data files under `database/seeders/data/*.php` if lists grow.  
6. Add environment gates (`APP_ENV=production` → refuse demo account seeders) when going live.  
7. Optional Composer script: `"seed": "php database/seed.php"`.

---

## Testing checklist

- [ ] Migrations applied through `031`  
- [ ] `php database/seed.php` completes without error  
- [ ] Second run completes without duplicate-key failures  
- [ ] `roles` contains `admin`, `employer`, `seeker`, `staff`  
- [ ] Countries / cities / categories / types / skills / languages / plans populated  
- [ ] Demo admin / employer / seeker emails exist once  
- [ ] Three demo jobs with `status = published` exist  
- [ ] Public pages and health routes still load  
- [ ] Auth services still resolve (unchanged)

---

*End of seeder architecture documentation.*

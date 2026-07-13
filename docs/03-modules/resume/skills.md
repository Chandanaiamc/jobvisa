# Resume Skills (Sprint 2D.6)

Resume-scoped skills for the Enterprise Resume Builder.

---

## Architecture audit

| Concern | Finding |
|---|---|
| Catalogue | Table is **`skills`** (not `master_skills`) — `005_create_skills_table` |
| Profile skills | **`user_skills`** is `user_id`-scoped (Sprint 2C). **Not used** by Resume Builder |
| Experience pivot | `work_experience_skills` tags skills on a role only |
| Prior resume skills | None — new `resume_skills` table |

**Source of truth**

- Catalogue: `skills`
- Resume links: `resume_skills` (`resume_id` + `skill_id` + metadata)
- Profile: `user_skills` remains untouched

---

## Migration

- Forward: `038_create_resume_skills.sql`
- Rollback: `038_create_resume_skills_rollback.sql`

Columns: `level`, `years_experience`, `last_used_year`, `is_primary`, `sort_order`, `status`, `deleted_at`, timestamps. Unique (`resume_id`, `skill_id`). Soft-deleted rows restored on re-add.

```bash
Get-Content database/migrations/038_create_resume_skills.sql -Raw | E:\localhost\mysql\bin\mysql.exe -u root jobvisa_db
```

---

## Routes

| Method | Path | Action |
|---|---|---|
| GET | `/jobseeker/resumes/{id}/skills` | index |
| GET | `/jobseeker/resumes/{id}/skills/search?q=` | autocomplete JSON |
| POST | `/jobseeker/resumes/{id}/skills` | store |
| GET | `/jobseeker/resumes/{id}/skills/{skill}/edit` | edit |
| POST | `/jobseeker/resumes/{id}/skills/{skill}` | update |
| POST | `/jobseeker/resumes/{id}/skills/{skill}/delete` | soft delete |
| POST | `/jobseeker/resumes/{id}/skills/{skill}/restore` | restore |
| POST | `/jobseeker/resumes/{id}/skills/reorder` | reorder |

Middleware: existing jobseeker group + CSRF on POSTs. Auth unchanged.

---

## Components

| Layer | Class |
|---|---|
| Controller | `ResumeSkillController` |
| Service | `ResumeSkillService` |
| DTO | `ResumeSkillDTO` |
| Validator | `ResumeSkillValidator` |
| Policy | `ResumeSkillPolicy` |
| Repository | `ResumeSkillRepository` |
| Catalogue | `SkillCatalogRepository::search` / `isActive` |

---

## Validation

- Skill from active catalogue required
- Level: `beginner` \| `intermediate` \| `advanced` \| `expert`
- Years 0–60; last used year 1950–(current+1)
- One primary skill (others cleared)
- Duplicate active skill rejected; soft-deleted duplicate restored

---

## Completion

Rebalanced weights (total **100**):

| Section | Weight |
|---|---|
| Title | 8 |
| Personal | 22 |
| Professional | 18 |
| CV | 11 |
| Education | 15 |
| Experience | 16 |
| **Skills** | **10** |

Skills complete when ≥1 non-deleted `resume_skills` row exists.

---

## Autocomplete

`GET .../skills/search?q=` returns `{ success, results:[{id,name,slug}] }` after resume view authorization. Client debounces typeahead in `jobseeker.js`.

---

## Manual testing

1. Search catalogue → add skill with level / years / last used / primary  
2. Edit metadata; reorder ↑↓; soft-delete → restore  
3. Completion increases by skills weight  
4. `/jobseeker/skills` (profile) still works; `user_skills` unchanged  
5. Education / Experience / Personal / Professional / CV / Resume CRUD intact  
6. Unauthorized actor cannot mutate  

---

## Rollback

Run `038_create_resume_skills_rollback.sql`. Catalogue and `user_skills` remain.

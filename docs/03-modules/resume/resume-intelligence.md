# Resume Intelligence Scoring Engine (Sprint 2F.1 + 2F.2)

Secure, explainable resume intelligence scoring — **separate from** resume completion percentage.

---

## Principles

- Transparent weighted rules (rules version `2F.2.0`)
- Scores always clamped to **0–100**
- Deterministic for the same resume data + target role
- No fabricated AI claims; ATS score is **not** an ATS approval guarantee
- No discriminatory scoring (protected traits never used)
- Private reference contacts never enter scoring context or public projections
- Completion `%` weights and calculator remain **unchanged**

---

## Architecture

| Layer | Location |
|---|---|
| Rules | `app/Domain/Resume/Intelligence/Rules/*` |
| Calculators | `ResumeIntelligenceCalculator`, `AtsReadinessCalculator`, `EmployerReadinessCalculator` |
| Keyword / gaps | `KeywordMatchingService`, `SkillGapAnalysisService`, `RoleSkillTaxonomy` |
| Context factory | `ResumeIntelligenceContextFactory` |
| Recommendations | `ResumeIntelligenceRecommendationService` |
| Service / Policy | `ResumeIntelligenceService`, `ResumeIntelligencePolicy` |
| Repositories | `ResumeIntelligenceRepository`, `ResumeIntelligenceHistoryRepository` |
| Controller / View | `ResumeIntelligenceController`, `views/.../intelligence.php` |

Controllers and views contain **no** scoring logic.

---

## Overall score categories (weights sum = 100)

Unchanged from 2F.1 (Profile 10 · Summary 12 · Education 10 · Experience 14 · Skills 10 · Languages 5 · Certifications 6 · Projects 8 · Achievements 5 · Publications 5 · Portfolio 5 · References 5 · Contact 5).

---

## Additional scores (2F.2)

| Score | Meaning |
|---|---|
| ATS readiness | Heuristic parsing readiness (not an ATS guarantee) |
| Employer readiness | Shareable / review-ready content |
| Keyword match | Coverage of role-expected keywords (0–100) |

Skill gap analysis reports present vs missing skills for the resolved role (coverage %). Both feed recommendations (`KW_MISSING`, `SKILL_GAP`) without changing overall category weights.

Optional POST field `target_role` on recalculate overrides role inference from headline/job title.

---

## Score history

Migration `048_extend_resume_intelligence_engine.sql`:

- Extends `resume_intelligence_snapshots` with `keyword_match_score`, `analysis_json`
- Creates `resume_intelligence_history` (append-only on recalculate, soft delete via `deleted_at`)

Each recalculate upserts the current snapshot **and** appends a history row.

---

## Routes

| Method | Path | Access |
|---|---|---|
| GET | `/jobseeker/resumes/{id}/intelligence` | Owner (view) |
| POST | `/jobseeker/resumes/{id}/intelligence/recalculate` | Owner + CSRF (`target_role` optional) |
| POST | `/jobseeker/resumes/{id}/intelligence/history/{historyId}/delete` | Owner + CSRF |
| POST | `/jobseeker/resumes/{id}/intelligence/history/clear` | Owner + CSRF |

---

## Privacy / public output

- `ResumeIntelligenceDTO::toPublicArray()` exposes scores/strength only (includes keyword match score)
- Reference email/phone stripped in context factory
- Protected personal fields never loaded into context

---

## Test cases

1. Empty resume → low valid scores, keyword/gap analysis, recommendations  
2. Recalculate with target role → history row appended  
3. Soft-delete history entry → hidden from list  
4. Scores always ∈ [0,100]; completion weight sum remains 100  
5. CSRF required on all intelligence POSTs  
6. Prior resume modules still resolve in DI  

---

## Rollback

- `048_extend_resume_intelligence_engine_rollback.sql` drops history + additive snapshot columns  
- `047_create_resume_intelligence_snapshots_rollback.sql` drops snapshot table  

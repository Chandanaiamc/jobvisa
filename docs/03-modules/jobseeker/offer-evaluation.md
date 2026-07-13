# AI Offer Evaluation Assistant (Sprint 3.9)

Evaluates a job offer against resume signals and salary intelligence using deterministic rules. **No external AI APIs.** PHP views.

---

## Capabilities

- Compensation / benefits / growth / lifestyle scores
- Overall offer score
- Accept / negotiate / decline recommendation
- Market band comparison
- Pros, cons, counter-offer guidance
- Negotiation talking points and decision checklist
- Evaluate / recalculate, PDF export
- History (soft delete, restore, purge, clear)

Rules version: `3.9.0`

---

## Routes

| Method | Path |
|---|---|
| GET | `/jobseeker/resumes/{id}/offer-evaluation` |
| POST | `…/evaluate` · `…/recalculate` |
| GET | `…/history` |
| POST | `…/history/{historyId}/delete` · `…/restore` · `…/purge` · `…/clear` |
| GET | `…/analyses/{analysisId}/export/pdf` |

Jobseeker ownership + CSRF on POSTs. Employer / non-owner denied.

---

## Persistence

Migration `063_create_offer_evaluation_tables.sql`:

- `offer_evaluation_analyses`
- `offer_evaluation_history`

Reuses Salary Intelligence and prior AI signals. Preserves Job Search Copilot and earlier modules.

# JobVisa.lk — Project Development Rulebook

**Status:** Official project constitution  
**Audience:** Engineers, product, QA, DevOps, and AI-assisted contributors  
**Scope:** All work on JobVisa.lk from design through production operations  

This rulebook defines how JobVisa.lk is built, reviewed, documented, secured, and released.  
When practice conflicts with preference, **this document wins** until a formal decision updates it.

---

## 1. Coding Standards

1. Write clear, readable code that a new team member can understand without tribal knowledge.
2. Prefer simple, explicit solutions over clever abstractions.
3. Keep functions and classes focused on a single responsibility.
4. Do not mix presentation, business rules, and data access in the same layer.
5. Controllers remain thin; business logic belongs in services; SQL belongs in repositories or dedicated data-access layers.
6. Avoid duplicated logic. Extract shared behavior into helpers or services only when reuse is real.
7. Fail fast on invalid input. Validate at system boundaries.
8. Do not leave dead code, commented-out blocks, or temporary hacks in the main branch.
9. Every change must preserve existing behavior unless the change is intentionally breaking and approved.
10. Comments explain **why**, not what the code already shows.
11. No framework lock-in assumptions beyond what the architecture documents allow.
12. Do not commit secrets, credentials, personal data dumps, or production exports.

---

## 2. Documentation Rules

1. `docs/` is the system of record for product, architecture, process, and delivery decisions.
2. Update documentation in the **same change** as behavior that makes docs wrong or incomplete.
3. Starter docs may begin brief; they must not remain misleading.
4. Use clear headings, short paragraphs, and precise language suitable for investors, engineers, and operators where relevant.
5. Do not put implementation secrets in documentation.
6. Architecture and module docs must stay aligned with the actual repository structure.
7. Decision records belong in the decisions log when a choice affects future work.
8. Changelogs describe user- or operator-visible change, not every internal commit.
9. Prefer updating an existing official doc over creating overlapping unofficial notes.
10. If documentation is deferred, record that deferral explicitly — do not silently skip it.

---

## 3. Git Rules

1. Never commit `.env` or any file containing real secrets.
2. Keep commits small, focused, and reversible.
3. Write commit messages that explain the purpose of the change.
4. Do not rewrite shared history on protected branches.
5. Do not force-push to `main` / `master` unless explicitly authorized emergency procedure is invoked.
6. Do not commit generated noise, local IDE junk, or personal scratch files.
7. Keep `storage/logs` and private upload contents out of version control; preserve `.gitkeep` markers only.
8. Branch names should reflect intent (feature, fix, docs, chore).
9. Pull requests (or equivalent reviews) are required before merging to the primary branch once the team process is active.
10. Do not bypass hooks or review gates without recorded approval.

---

## 4. Database Rules

1. MySQL is the system of record. Schema changes are deliberate, reviewed, and logged.
2. All tables use consistent naming: snake_case, clear entity names, stable primary keys.
3. Foreign keys and relationships must match the approved data model unless a documented exception exists.
4. Never invent ad-hoc tables in production without migration tracking.
5. Every schema change is recorded in the migration log with date, author, and purpose.
6. Prefer additive, backward-compatible migrations. Destructive changes require explicit approval and a rollback plan.
7. Indexes are designed for real query paths — especially search, feeds, applications, and authentication lookups.
8. Do not store secrets in the database in plaintext.
9. Soft-delete and retention rules must respect privacy and business recovery needs.
10. No direct production schema editing “by hand” without an auditable migration equivalent.
11. Seed data for local/staging must never overwrite production data.

---

## 5. Security Rules

1. Security is a product requirement, not a final polish step.
2. The public web root must not expose application source, configuration, logs, or private uploads.
3. Authentication and authorization are enforced on the server for every sensitive action.
4. Passwords are stored only as modern one-way hashes.
5. Sessions and tokens are protected against theft and fixation; admin access is isolated from public user sessions.
6. Protect against injection, XSS, CSRF, and unsafe file upload execution.
7. Uploaded documents (especially CVs) are private by default and served only to authorized parties.
8. Rate-limit authentication and other abuse-prone endpoints.
9. Log security-relevant events; never log passwords, raw tokens, or full payment credentials.
10. Least privilege applies to people, services, and database accounts.
11. Report suspected vulnerabilities immediately through the agreed channel; do not silently ignore them.

---

## 6. Code Review Rules

1. Every meaningful change is reviewable: clear description, scope, and test notes.
2. Reviewers check correctness, security, readability, architecture fit, and documentation impact.
3. Review comments must be respectful, specific, and actionable.
4. Authors respond to feedback or record why a suggestion is declined.
5. “Looks good” is insufficient for high-risk areas: auth, payments, uploads, admin, and migrations.
6. Do not approve changes you do not understand.
7. Large changes should be split when review quality would otherwise suffer.
8. Blocking issues (security, data loss, broken contracts) must be fixed before merge.
9. Non-blocking suggestions may be follow-ups if explicitly tracked.
10. AI-generated code receives the same review rigor as human-written code.

---

## 7. Naming Conventions

| Area | Convention |
|---|---|
| PHP classes/files | PascalCase; filename matches class name |
| Methods/functions | camelCase; verbs for actions |
| Variables | camelCase; descriptive names |
| Constants | UPPER_SNAKE_CASE |
| Database tables | snake_case, plural |
| Database columns | snake_case |
| Foreign keys | `{entity}_id` |
| Routes/URLs | kebab-case |
| CSS/JS files | kebab-case |
| Documentation files | kebab-case Markdown |
| Environment variables | UPPER_SNAKE_CASE |
| Branches | `feature/…`, `fix/…`, `docs/…`, `chore/…` |

Names must reveal intent. Avoid abbreviations unless they are industry-standard and widely understood on the team.

---

## 8. Folder Rules

1. Respect the approved project structure. Do not invent parallel shadow trees.
2. Publicly reachable files belong only under the designated public entry areas.
3. Application code, configuration, and private storage remain outside direct web exposure.
4. Documentation belongs in `docs/` under the numbered section structure.
5. Routes, config, database artifacts, admin, and API areas stay separated by responsibility.
6. Do not place temporary experiments in production folders without a removal plan.
7. Assets are organized by type and surface (public vs admin) for maintainability.
8. If a new top-level folder is required, record the decision before adopting it widely.
9. Keep uploads and logs in storage paths reserved for runtime data.
10. Do not delete structural marker files (such as `.gitkeep`) that preserve empty directories.

---

## 9. Testing Rules

1. Critical flows must be testable: authentication, job apply, job post, payments, admin moderation, and uploads.
2. Write or update test cases when behavior changes.
3. Bugs that escape to shared environments require a regression note in the bug log and, where practical, a lasting test.
4. Test on mobile-first viewports for user-facing pages.
5. Do not treat “works on my machine” as sufficient for release candidates.
6. Security-sensitive changes require explicit verification steps in the review/test notes.
7. Data used in testing must be fake or anonymized — never production personal data.
8. Record known test gaps honestly rather than implying coverage that does not exist.
9. Automated tests, when introduced, must be deterministic and maintainable.
10. A release is not done until agreed test gates for that release are green or formally waived.

---

## 10. Deployment Rules

1. Local, staging, and production configurations are separated; production debug mode stays off.
2. Deploy only from known, reviewable states of the codebase.
3. Environment variables and secrets are injected by environment — never hard-coded.
4. Database migrations run in a controlled order with backup awareness.
5. Deployments include a smoke-check of critical pages and authentication.
6. Rollbacks must be possible; know the previous good version before promoting a release.
7. Production access is limited to authorized operators.
8. Post-deploy issues are logged, triaged, and communicated quickly.
9. Do not deploy experimental branches directly to production.
10. Hosting, SSL, backups, and monitoring expectations are followed as defined in deployment docs.

---

## 11. AI Usage Rules

1. AI tools may assist with drafting, refactoring suggestions, documentation, and tests — humans remain accountable.
2. AI output is never merged without human review for correctness, security, and architecture fit.
3. Do not paste secrets, production data, or private candidate documents into external AI tools.
4. AI must follow this rulebook and the approved architecture; it may not invent conflicting structures silently.
5. Prefer AI for acceleration, not for bypassing design, review, or testing discipline.
6. When AI proposes a large architectural change, require an explicit human decision record.
7. Generated code must match project naming, folder, and security rules.
8. Do not allow AI to delete or overwrite critical files unless the change is intentional, reviewed, and scoped.
9. Credit and transparency: significant AI-assisted changes should be noted in PR/review context when useful for auditors.
10. If AI guidance conflicts with this constitution, **this constitution prevails**.

---

## 12. Performance Rules

1. Design for growth from day one: indexes, lean queries, and efficient page payloads.
2. Avoid N+1 query patterns and unbounded list loads.
3. Paginate large collections (jobs, applications, messages, admin tables).
4. Optimize for mobile networks: minimize unnecessary assets and blocking scripts.
5. Cache only with a clear invalidation strategy; do not cache personalized or sensitive data unsafely.
6. Measure before micro-optimizing; fix proven bottlenecks first.
7. Background work (email/SMS queues) must not block user-facing requests.
8. Images and uploads have size limits and sensible formats.
9. Admin and public performance budgets may differ, but neither may become negligently slow.
10. Performance regressions that harm core journeys are release blockers.

---

## 13. UI Consistency Rules

1. JobVisa.lk must feel like one product across public, seeker, employer, and admin surfaces.
2. Follow the design system once established: typography, color, spacing, components, and tone.
3. Mobile-first layout is mandatory for public and seeker experiences.
4. Prefer clarity and trust over decorative complexity.
5. Reuse shared UI patterns (navigation, forms, alerts, buttons) instead of one-off variants.
6. Accessibility basics matter: readable contrast, focus states, labels, and usable controls.
7. Empty, loading, and error states are designed — not left blank or cryptic.
8. Do not introduce a new visual style for a single page without design approval.
9. Admin UI may be denser, but must remain consistent and professional.
10. Copy is part of UI: clear, respectful, and free of misleading claims.

---

## 14. Versioning Rules

1. Use clear version identifiers for releases (for example, semantic versioning once release cadence begins).
2. Every production release has a changelog entry.
3. API versions are explicit; breaking API changes require a new version path or a documented migration window.
4. Database migrations are ordered and never silently rewritten after they have run in shared environments.
5. Documentation versions/dates are updated when governance or architecture materially changes.
6. Do not reuse version numbers for different artifacts.
7. Tag releases in Git when the release process is active.
8. Hotfixes increment versions according to the agreed scheme and are documented.
9. Deprecated features are marked before removal, with a timeline.
10. “Latest” is not a substitute for a precise version in production communications.

---

## 15. Change Management Rules

1. Material product, architecture, security, or process changes require a recorded decision.
2. Scope changes mid-sprint/release must be acknowledged; silent scope creep is not allowed.
3. High-risk changes (auth, payments, schema, permissions) need extra review and a rollback plan.
4. Communicate user-visible changes to stakeholders before or at release, as appropriate.
5. Emergency changes are allowed to protect users or uptime, then followed by documentation and review within an agreed window.
6. Deprecations and removals follow announce → migrate → remove — not surprise deletion.
7. Conflicts between speed and safety are escalated; safety wins for trust-critical paths.
8. Updates to this rulebook itself require explicit approval and a decisions-log entry.
9. Temporary exceptions to these rules must be time-bounded and written down.
10. Sustained violation of this constitution is a process failure and must be corrected at the team level.

---

## Governance

- **Owner:** Project leadership (Product + Engineering)  
- **Amendment process:** Propose change → review → record in decisions log → update this file  
- **Precedence:** Security and data-integrity rules override delivery convenience  

---

*JobVisa.lk Project Development Rulebook — the official constitution of the project.*

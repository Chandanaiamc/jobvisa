# Technology Stack Review

**Project:** JobVisa.lk Enterprise  
**Document type:** Pre-feature technology stack review  
**Audience:** Engineering, Product, Security, DevOps, Investors  
**Status:** Reference architecture — documentation only  
**Constraint:** This document does not modify application code  

---

## Backend

| Technology | Role on JobVisa.lk |
|---|---|
| **PHP 8.2+** | Primary application runtime |
| **Composer** | Dependency and PSR-4 autoload management |
| **PSR-4** | Standard namespace-to-directory mapping |
| **MVC** | Request → Controller → View / JSON separation |
| **Dependency Injection** | Explicit wiring of services and repositories |
| **Repository Pattern** | Isolates SQL behind data-access interfaces |
| **Service Layer** | Owns business use-cases (auth, jobs, apply) |
| **PDO** | Database access with prepared statements |

### Why each is selected

**PHP 8.2+**  
Matches the local/production target, provides modern typing, enums, readonly properties, and performance improvements. Widely hosted in Sri Lanka and Gulf-facing stacks; large talent pool for long-term maintenance.

**Composer**  
Industry standard for PHP packages and autoloading. Enables controlled adoption of libraries (PHPUnit, future mail/HTTP clients) without a full framework lock-in.

**PSR-4**  
Predictable class loading (`JobVisa\App\` → `app/`, plus legacy `App\` during transition). Reduces “file not found” chaos as the codebase grows toward enterprise size.

**MVC**  
Clear separation for a multi-surface product (public site, admin, API). Controllers stay thin; views remain replaceable when the frontend evolves.

**Dependency Injection**  
Services depend on abstractions (repositories, mailers, rate limiters). Improves testability and allows swapping file sessions for Redis later without rewriting controllers.

**Repository Pattern**  
All SQL lives in one place. Protects against duplicated queries, eases indexing changes, and supports read-replica routing in later versions.

**Service Layer**  
Encodes business rules once for web, admin, and API. Prevents “logic in controllers” drift that kills maintainability on recruitment platforms.

**PDO**  
Native, stable, and sufficient for MariaDB/MySQL. Enforces prepared statements, exception mode, and avoids ORM lock-in until query patterns are proven.

---

## Frontend

| Technology | Role on JobVisa.lk |
|---|---|
| **HTML5** | Semantic, accessible markup |
| **CSS3** | Layout, responsive design, design tokens |
| **Bootstrap 5** | Rapid, consistent UI components and grid |
| **Vanilla JavaScript** | Progressive enhancement without a SPA mandate |

### Why each is selected

**HTML5**  
SEO-critical for job and company pages; works without JS for core content; aligns with accessibility expectations.

**CSS3**  
Full control of brand presentation; mobile-first media queries; no build step required for Version 1.

**Bootstrap 5**  
Accelerates forms, navigation, and admin density while remaining customizable. Acceptable for enterprise MVP speed; can be scoped to admin later if the public brand needs a fully custom system.

**Vanilla JavaScript**  
Keeps Version 1 simple (filters, mobile nav, form UX). Avoids bundler complexity until product–market fit and API contracts are stable.

### Future migration path

| Target | When it makes sense | Migration approach |
|---|---|---|
| **Vue.js** | Interactive seeker/employer dashboards need reactive UI | Mount Vue on specific pages first; consume REST API; keep PHP for SEO pages |
| **React** | Team preference or shared component libraries with partners | Same strangler pattern: API-backed widgets → route-level React islands |
| **Next.js** | Need SSR/SSG marketing + app hybrid at CDN edge | Next consumes JobVisa REST/OAuth APIs; PHP becomes BFF/API + admin, or gradually shrinks |

**Principle:** Do not rewrite the frontend until the **API surface is stable**. MVC views remain the source of truth through Version 1–2; SPAs/Next attach as clients, not replacements of business logic.

---

## Database

| Technology / practice | Role |
|---|---|
| **MariaDB** | Primary relational store (localhost/production-friendly) |
| **MySQL compatibility** | Portable SQL, hosting flexibility |
| **Indexes** | Feed, search, auth, and application hot paths |
| **Foreign Keys** | Referential integrity for users, jobs, applications |
| **Transactions** | Atomic apply/pay/verify workflows |

### Why this database approach

**MariaDB**  
Strong InnoDB performance, familiar operations model, excellent fit for XAMPP/local and common VPS/cloud images in the region.

**MySQL compatibility**  
Schema and PDO DSN remain portable across MariaDB and MySQL 8 deployments, reducing vendor lock risk.

**Indexes**  
Required for million-user scale on `users.email`, job feed filters, applications by user/job, and token lookups.

**Foreign Keys**  
Prevent orphan applications, invalid `role_id`, and broken company–job links — critical for trust in a recruitment marketplace.

**Transactions**  
Registration (user + profile), applications, payments, and verification flows must commit or roll back as a unit.

---

## API

| Capability | Status |
|---|---|
| **REST API** | Target integration style (`/api/v1/...`) |
| **OAuth2** | Future — partner and “Login with” flows |
| **JWT** | Future — mobile/stateless access tokens (or opaque tokens) |
| **Mobile App API** | Future — same auth domain, token guards |
| **Third-party integrations** | Future — HR ATS, payment webhooks, SMS providers |

### Why REST first

REST over JSON is universally supported by mobile, partners, and frontend frameworks. Versioning (`v1`) protects clients when fields evolve. OAuth2/JWT arrive after session auth and RBAC are proven on the web, reusing the same service layer.

---

## Security

| Control | Purpose |
|---|---|
| **CSRF** | Stop cross-site state-changing requests on session auth |
| **XSS** | Escape output; sanitize rich text; reduce script injection |
| **CSP** | Browser-enforced content restrictions |
| **Rate Limiting** | Mitigate brute force and abuse on login/apply/post |
| **RBAC** | Role-based access via `roles` / `role_id` (permissions later) |
| **Password Hashing** | `password_hash` / `password_verify` (Argon2id preferred) |
| **Secure Sessions** | HttpOnly, Secure, SameSite; regenerate on login |

Security is treated as a product requirement for an overseas employment marketplace where CVs and trust are high-value assets.

---

## Performance

| Practice | Purpose |
|---|---|
| **Lazy Loading** | Defer non-critical images/components; load relations only when needed |
| **Caching** | Cache taxonomies, role lookups, public job fragments |
| **Pagination** | Bound list queries (jobs, applications, admin tables) |
| **Prepared Statements** | Security + plan reuse via PDO |
| **Database Indexing** | Keep p95 latency acceptable under growth |

Performance work follows measurement: index and paginate first; introduce Redis caching when file/DB limits appear.

---

## Future Integrations

| Integration | Role in the platform |
|---|---|
| **Redis** | Cache, sessions, rate-limit counters |
| **Queue Workers** | Email, SMS, AI scoring, webhooks — async |
| **Docker** | Repeatable local/staging/production images |
| **GitHub Actions** | Automated test/lint on pull requests |
| **CI/CD** | Controlled deploy pipelines with migrations |
| **AWS** | Compute, managed DB, object storage options |
| **Cloudflare** | DNS, WAF, DDoS, edge caching |
| **CDN** | Static assets and public media |
| **Object Storage** | Resumes, logos, documents (private buckets) |
| **Email Queue** | Transactional mail at scale |
| **SMS Gateway** | OTP and high-priority alerts |
| **AI Recommendation Engine** | Job–candidate matching assists |
| **Search Engine** | Full-text / OpenSearch / Meilisearch for jobs |

Each integration plugs in behind interfaces (cache, queue, storage, mail) so Version 1 stays simple while Version 3–5 scale out.

---

## Roadmap

### Version 1 — Foundation marketplace

- PHP 8.2+ MVC + Composer PSR-4  
- MariaDB schema + PDO repositories/services  
- Server-rendered HTML5/CSS3/Bootstrap 5 + vanilla JS  
- Session auth, CSRF, RBAC (roles), password hashing  
- Public jobs/companies pages; seeker apply; employer post (core)  
- REST API skeleton (`/api/v1`) read-only or auth-basic  
- File sessions; DB email queue table; local/staging deploy  

### Version 2 — Trust, monetization, API clients

- Full auth hardening (verification, reset, lockout, audit)  
- Employer verification + admin moderation  
- Packages/payments integration  
- REST API for mobile MVP (token auth decision: opaque or JWT)  
- Redis for cache/rate limits  
- Queue workers for email/SMS  
- CI with GitHub Actions + PHPUnit gate  

### Version 3 — Scale and richer clients

- Vue or React islands for dashboards (strangler)  
- OAuth2 for partners; expanded third-party webhooks  
- Object storage for CVs; CDN for public assets  
- Cloudflare WAF; Dockerized deployments  
- Search engine for job discovery  
- DB read replicas; session store in Redis  

### Version 4 — Intelligence and multi-channel

- AI recommendation engine (assistive, audited)  
- Advanced matching and moderation assists  
- Next.js marketing/app hybrid **optional** if SEO/product demands  
- SMS gateway + WhatsApp-class channels as product allows  
- Multi-region CDN and hardened CI/CD to AWS (or equivalent)  

### Version 5 — Platform maturity

- Fine-grained permissions, multi-tenant agency workspaces  
- Full event-driven workers; observability (metrics/tracing)  
- Mature mobile apps on stable API contracts  
- Enterprise integrations (ATS, HRIS) via OAuth2  
- Continuous performance/security programs; DR across regions  

---

## Stack principles (non-negotiable)

1. **Business logic lives in PHP services**, not in the frontend framework of the day.  
2. **Database integrity before convenience** — FKs, transactions, indexes.  
3. **API-first evolution** — web and mobile are clients of the same domain.  
4. **Adopt infrastructure when metrics demand it** — Redis/queues/CDN are Version 2+, not blockers for Version 1.  
5. **No premature rewrite** — Bootstrap + vanilla JS is intentional until API and UX complexity justify Vue/React/Next.

---

## Document control

| Item | Value |
|---|---|
| Owner | Chief Software Architect |
| Review cycle | Each major version planning |
| Related docs | `authentication-foundation-review.md`, `mvc-framework-audit.md`, `project-rules.md` |

---

*End of technology stack review. No application code was modified.*

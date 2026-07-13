# MVC Framework Audit Report

**Project:** JobVisa.lk  
**Date:** 11 July 2026  
**Auditor role:** Senior PHP Software Architect  
**Scope:** Compare the current lightweight MVC foundation with a professional custom MVC framework baseline  
**Constraint:** No code was created or modified for this audit. Approval is required before remediation.

---

## Executive summary

JobVisa.lk has a **usable skeleton kernel** (autoload, App, Router, Controller, Model, Config, View, helpers, public front controller, `.env`). It is **not yet a complete professional MVC framework**.

Critical gaps include: Request/Response objects, middleware pipeline, exception handling, logging, dependency injection, database abstraction, validation, service/repository layers, route parameters, API/admin bootstraps, Composer PSR-4, URL rewriting, and empty application layers (`controllers`, `models`, `views`, `middleware`).

**Architecture score: 42 / 100**

---

## 1. Existing files

### Framework / bootstrap
| Path | Status |
|---|---|
| `bootstrap/autoload.php` | Present — custom namespace autoloader |
| `bootstrap/app.php` | Present — boots env, config, App |
| `bootstrap/.htaccess` | Present — deny direct access |
| `public/index.php` | Present — front controller |
| `app/Core/App.php` | Present — kernel + route dispatch |
| `app/Core/Router.php` | Present — GET/POST, callable / `Controller@method` |
| `app/Core/Controller.php` | Present — base controller |
| `app/Core/Model.php` | Present — attribute bag only (no DB) |
| `app/Core/Config.php` | Present — `.env` + config array loader |
| `app/Core/View.php` | Present — basic PHP view renderer |
| `app/helpers/functions.php` | Present — `env`, `config`, `view`, `e`, `url`, `asset`, `redirect`, `base_path` |
| `app/.htaccess` | Present — deny direct access |

### Config / env / routes
| Path | Status |
|---|---|
| `.env` | Present |
| `.env.example` | Present |
| `.gitignore` | Present (minimal) |
| `config/app.php` | Present — array config |
| `config/database.php` | Present — **legacy mysqli script** (not framework-integrated; skipped by Config loader) |
| `routes/web.php` | Present — smoke route only |
| `routes/api.php` | Present — **empty file** |

### Application placeholders (empty or markers only)
| Path | Status |
|---|---|
| `app/controllers/` | Folder exists — **no controllers** |
| `app/models/` | Folder exists — **no models** |
| `app/views/` | Folder exists — **no views** |
| `app/middleware/` | Folder exists — **no middleware classes** |
| `admin/` | Folder exists — **empty** |
| `api/` | Folder exists — **empty** |
| `public/assets/css|js|images/` | Folders exist — **empty** |
| `database/migrations/` | `.gitkeep` only |
| `storage/logs/` | `.gitkeep` only |
| `storage/uploads/` | `.gitkeep` only |

### Outside the MVC kernel (pre-existing)
| Path | Status |
|---|---|
| `index.php` (project root) | Present — standalone homepage (not wired to MVC) |
| `docs/**` | Present — documentation tree |

---

## 2. Missing files

Compared with a professional lightweight MVC (enterprise custom PHP, not Laravel):

### HTTP kernel
| Missing file (recommended) | Why it matters |
|---|---|
| `app/Core/Request.php` | Normalize method, URI, input, headers, files |
| `app/Core/Response.php` | Unified HTML/JSON/redirect responses + status codes |
| `app/Core/Kernel.php` or pipeline in `App` | Middleware → router → controller lifecycle |
| `app/Core/ExceptionHandler.php` | Catch errors; safe pages in production; debug in local |
| `app/Core/Logger.php` | Write to `storage/logs` |
| `app/Core/Container.php` | Simple DI / service resolution |
| `app/Core/Database.php` | PDO factory (connection only; still no business SQL) |
| `app/Core/Session.php` | Session start/config helpers |
| `app/Core/Csrf.php` | CSRF token generate/verify foundation |
| `app/Core/Validator.php` | Input validation foundation |
| `app/Core/MiddlewareInterface.php` | Middleware contract |

### Middleware (examples)
| Missing file | Purpose |
|---|---|
| `app/middleware/Middleware.php` (base) | Shared middleware base (optional) |
| `app/middleware/CorsMiddleware.php` | API CORS foundation |
| `app/middleware/CsrfMiddleware.php` | Form protection |
| `app/middleware/GuestMiddleware.php` / `AuthMiddleware.php` | Auth gates (stubs OK later) |

### Routing / entry
| Missing file | Purpose |
|---|---|
| `routes/admin.php` | Admin route map |
| `public/.htaccess` | Front-controller rewrite + security headers baseline |
| `admin/index.php` | Admin front controller |
| `api/index.php` | API front controller |
| Error views e.g. `app/views/errors/404.php`, `500.php` | Professional error UX |
| Layout e.g. `app/views/layouts/main.php` | Shared layout support |

### Tooling / standards
| Missing file | Purpose |
|---|---|
| `composer.json` | Official PSR-4 autoload + future packages |
| `phpunit.xml` + `tests/` bootstrap | Test harness |
| `config/session.php`, `config/logging.php`, `config/cors.php` | Split config by concern |
| Updated `.gitignore` exceptions for `.gitkeep` | Keep empty storage dirs in Git cleanly |

### Intentionally deferred (still “missing” vs complete MVC)
- Authentication / authorization classes  
- Repository / Service base classes  
- Mail / queue workers  
- Migration runner  

These are expected later, but they affect the completeness score.

---

## 3. Missing folders

| Missing / incomplete folder | Recommendation |
|---|---|
| `app/Services/` | Business orchestration layer (empty OK initially) |
| `app/Repositories/` | Data-access isolation |
| `app/Exceptions/` | Typed HTTP/domain exceptions |
| `app/DTO/` or `app/Support/` | Optional but common in enterprise PHP |
| `app/views/layouts/` | Layout templates |
| `app/views/components/` | Reusable partials |
| `app/views/errors/` | 404/500 templates |
| `storage/cache/` | File cache placeholder |
| `storage/sessions/` | Session files (if file driver) |
| `tests/Unit/` and `tests/Feature/` | Automated testing |
| `scripts/` | CLI migrate/seed/log-rotate later |

**Note:** `admin/`, `api/`, `app/controllers`, `app/models`, `app/views`, `app/middleware` exist but are empty shells — structurally present, functionally missing.

---

## 4. Recommended improvements

### Priority P0 — framework completeness (before features)
1. Add **Request** and **Response** objects; stop reading `$_SERVER`/`echo` ad hoc in the long term.  
2. Add a **middleware pipeline** and wire it in `App::run()`.  
3. Add **ExceptionHandler** + error views; honor `APP_DEBUG`.  
4. Add **Logger** writing to `storage/logs`.  
5. Add **PDO Database** wrapper (connect only; no business queries yet).  
6. Replace/avoid reliance on legacy `config/database.php` mysqli side effects.  
7. Add `public/.htaccess` rewrite so `public/` is the true document root path.  
8. Support **route parameters** (`/jobs/{id}`), route groups, and named routes.  
9. Load `routes/api.php` (and later `routes/admin.php`) from dedicated entry points.  
10. Introduce **Composer PSR-4** while keeping the custom autoload as fallback or replacing it cleanly.

### Priority P1 — professional structure
11. Base **Validator**, **Session**, **CSRF** utilities.  
12. Empty but official `Services/` and `Repositories/` folders + base classes.  
13. View **layouts** (`@section`-style or simple `layout + content` pattern).  
14. Unify entry strategy: root `index.php` vs `public/index.php` (document and eventually single front controller).  
15. Expand `.gitignore` with `!.gitkeep` exceptions.  
16. Add config files for session, logging, cors, uploads.

### Priority P2 — enterprise readiness
17. PHPUnit test skeleton.  
18. API response envelope helper.  
19. Admin bootstrap isolation (separate session cookie later).  
20. Health/status route for ops (non-business).  
21. Coding standards alignment with `docs/09-project-management/project-rules.md`.

### Architectural risks to resolve
- **Two public faces:** root `index.php` (marketing page) and `public/index.php` (MVC) can confuse deployment and routing.  
- **Legacy DB script:** `config/database.php` connects and echoes on include — unsafe if ever required accidentally.  
- **Router maturity:** no parameters, no middleware, no API/admin dispatch.  
- **Model layer:** not persistence-ready.  
- **Empty layers:** controllers/models/views/middleware invite inconsistent future code placement.

---

## 5. Architecture score (out of 100)

| Area | Max | Score | Notes |
|---|---:|---:|---|
| Directory / separation of concerns | 15 | 9 | Good folder intent; many layers empty |
| Front controller & bootstrap | 10 | 7 | `public/index.php` + bootstrap exist; dual root entry |
| Routing | 10 | 4 | Basic GET/POST only; no params/groups/API wiring |
| Controllers / Views | 10 | 5 | Base classes exist; no layouts/error views/controllers |
| Models / data access | 10 | 2 | Attribute model only; no PDO layer |
| Config / environment | 10 | 7 | `.env` + Config solid; legacy DB config conflict |
| Middleware / HTTP abstractions | 10 | 1 | Missing Request/Response/pipeline |
| Security foundations | 10 | 3 | Deny `.htaccess` only; no CSRF/session/headers baseline |
| Errors / logging | 5 | 1 | Missing handler + logger |
| Tooling (Composer, tests) | 5 | 1 | Not present |
| Docs / governance alignment | 5 | 2 | Docs exist; framework not fully aligned yet |
| **Total** | **100** | **42** | Incomplete professional MVC foundation |

### Score interpretation
| Range | Meaning |
|---|---|
| 80–100 | Production-ready custom MVC kernel |
| 60–79 | Solid foundation; safe to build features with care |
| 40–59 | Skeleton only — finish kernel before product modules |
| &lt;40 | Not yet an MVC framework |

**Current verdict:** Skeleton kernel. **Do not build business modules on top until P0 gaps are closed** (or explicitly accepted as debt).

---

## Comparison snapshot vs professional MVC

| Capability | Professional MVC | JobVisa.lk today |
|---|---|---|
| Front controller | Yes | Partial (`public/` only) |
| Router | Params, groups, named | Basic static paths |
| Middleware | Pipeline | Missing |
| Request/Response | First-class | Missing |
| DI container | Yes (simple OK) | Missing |
| Views + layouts | Yes | View only |
| ORM/Query or PDO layer | Yes | Missing |
| Validation | Yes | Missing |
| Exceptions + logs | Yes | Missing |
| Auth foundation | Yes | Intentionally deferred |
| Tests | Yes | Missing |
| Composer PSR-4 | Yes | Custom autoload only |

---

## Approval gate

**No files were modified for remediation.**

Awaiting approval to implement improvements. Recommended first approval package:

1. P0 HTTP kernel (Request, Response, ExceptionHandler, Logger, Middleware pipeline)  
2. PDO `Database` wrapper (connect only)  
3. `public/.htaccess` + error views  
4. Composer PSR-4  
5. Clarify root `index.php` vs `public/index.php` strategy (without deleting the working homepage unless approved)

---

*End of audit report.*

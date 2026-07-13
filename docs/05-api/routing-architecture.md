# Enterprise Routing Architecture

**Project:** JobVisa.lk  
**Status:** Active — existing public URLs preserved  

---

## Overview

Routing is organized through `JobVisa\App\Routing\RouteRegistrar`, which wraps the existing `App\Core\Router` (GET/POST, controller actions, 404 handling). Route files are loaded by group via `config/routing.php` when `App::run()` resolves `RouteRegistrar` from the container.

---

## Route loading order

Configured in `config/routing.php` → `load_order`:

```text
1. web
2. health
3. auth
4. jobseeker
5. employer
6. admin
7. api
```

Bootstrap sequence:

```text
Service providers boot
  → App constructed (uses shared Router singleton)
  → App::run()
      → RouteServiceProvider::loadRoutes()
      → RouteRegistrar::loadConfiguredRoutes()
      → Router::dispatch()
```

---

## Route groups

| Group | File | Prefix | Middleware tags (metadata) | Purpose |
|---|---|---|---|---|
| `web` | `routes/web.php` | _(none)_ | `web` | Public pages (`/`, `/about`, …) |
| `health` | `routes/health.php` | _(none)_ | `web` | `/health/database`, `/health/container` |
| `auth` | `routes/auth.php` | _(none)_ | `web` | Future login/register/logout |
| `jobseeker` | `routes/jobseeker.php` | _(none)_ | `web`, `auth`, `jobseeker` | Future seeker area |
| `employer` | `routes/employer.php` | _(none)_ | `web`, `auth`, `employer` | Future employer area |
| `admin` | `routes/admin.php` | `/admin` | `web`, `auth`, `admin` | Future admin panel |
| `api` | `routes/api.php` | `/api` | `api` | Future JSON API |

**Unchanged public URLs:** `/`, `/about`, `/contact`, `/jobs`, `/companies`, `/health/database`, `/health/container`.

---

## Middleware strategy

Today:

- Groups attach **middleware name tags** to routes via `RouteRegistrar`.
- Tags are stored in `RouteRegistrar::routeMiddlewareMap()` for a future pipeline.
- `App\Core\Router` still dispatches directly to controllers (compatible behaviour).

Next step (not in this change):

1. Implement middleware classes (`AuthMiddleware`, `CsrfMiddleware`, …).  
2. Resolve tags → classes in a `MiddlewarePipeline`.  
3. Run pipeline before `Router::runAction`.  

Suggested tag mapping:

| Tag | Intended middleware |
|---|---|
| `web` | Start session, CSRF on mutating verbs |
| `api` | JSON errors, throttling, optional bearer auth |
| `auth` | Require authenticated user |
| `admin` / `employer` / `jobseeker` | Role checks via `role_id` |

---

## Future API versioning

Use nested groups under `api`:

```php
$router->group('api_v1', function ($router) {
    $router->get('/jobs', 'Api\\V1\\JobApiController@index');
}, ['prefix' => '/api/v1', 'middleware' => ['api']]);
```

Or keep `routes/api.php` with explicit `/v1/...` paths inside the `/api` prefix group → final paths `/api/v1/...`.

Never break `v1` clients when introducing `v2`.

---

## Mobile API support

- Mobile apps consume the **`api` group** only (JSON), not server-rendered `web` routes.  
- Auth foundation (`AuthManager`) will back token/session guards attached to `api` middleware later.  
- Versioned paths (`/api/v1`) isolate mobile contracts from browser page URLs.  
- Health checks remain on `web`/`health` for ops; optional `/api/v1/health` can be added later without colliding.

---

## Key classes

| Class | Responsibility |
|---|---|
| `App\Core\Router` | Match method+URI → action; 404 |
| `JobVisa\App\Routing\RouteRegistrar` | Groups, prefixes, middleware tags, file loading |
| `JobVisa\App\Providers\RouteServiceProvider` | Bind Router + Registrar |

---

## Testing checklist

- [ ] `/` home page loads  
- [ ] `/about`, `/contact`, `/jobs`, `/companies` unchanged  
- [ ] `/health/database` still works (when DB available)  
- [ ] `/health/container` still works in local/debug  
- [ ] Unknown path still returns custom 404  
- [ ] Empty groups (`auth`, `api`, …) do not 500 on boot  
- [ ] No authentication behaviour change  

---

*End of routing architecture documentation.*

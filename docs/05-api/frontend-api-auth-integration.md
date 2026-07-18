# Frontend API Auth Integration (Phase 1)

**Branch:** `feature/frontend-api-auth-integration`  
**Scope:** Auth only — jobs, resumes, employer, admin, and notifications are **not** wired.

---

## Goal

Connect the browser login UX to Auth Token Lifecycle (`AuthLifecycleService`) without putting access/refresh tokens in JavaScript-readable storage.

---

## Architecture

| Layer | Role |
|---|---|
| Classic MVC | `POST /login` / `POST /logout` via `WebAuthController` (session + CSRF) — **preserved** |
| Bridge | After web login, mint lifecycle tokens into **httpOnly** cookies |
| JSON bridge | `POST/GET /auth/api/*` — same-origin, CSRF on mutations, cookies hold tokens |
| JS client | `public/assets/js/api-client.js` + `auth-api.js` — never reads token values |

### Cookies (httpOnly, SameSite from session config)

| Cookie | Contents |
|---|---|
| `jobvisa_api_access` (configurable) | Short-lived access token plaintext |
| `jobvisa_api_refresh` | Long-lived refresh token plaintext |

JSON responses **do not** include `access_token` or `refresh_token`.

---

## Endpoints (same-origin)

| Method | Path | CSRF | Behaviour |
|---|---|---|---|
| `POST` | `/auth/api/login` | Yes | Lifecycle login → set cookies + PHP session |
| `POST` | `/auth/api/refresh` | Yes | Rotate refresh cookie → new cookies |
| `GET` | `/auth/api/me` | No | Resolve access cookie → user payload |
| `POST` | `/auth/api/logout` | Yes | Revoke session tokens, clear cookies + web session |

Underlying lifecycle API (`/api/v1/auth/*`) remains for SDK/mobile; the browser uses the bridge.

---

## Frontend files

| File | Purpose |
|---|---|
| `public/assets/js/api-client.js` | Fetch wrapper, CSRF, **one** refresh retry on 401 |
| `public/assets/js/auth-api.js` | Login form progressive enhancement |
| `app/views/auth/login-form.php` | `data-api-auth-login` + status region |
| `app/views/auth/layout.php` | CSRF meta, `data-app-base`, script tags |

No-JS: form still posts to `/login` (session MVC). With JS: intercept → `/auth/api/login`.

---

## Config

`config/frontend.php` → `api_auth.enabled`, `access_cookie`, `refresh_cookie`  
Env: `FRONTEND_API_AUTH_ENABLED`, `FRONTEND_API_ACCESS_COOKIE`, `FRONTEND_API_REFRESH_COOKIE`

---

## Security assumptions

1. Tokens never appear in HTML, URLs, `localStorage`, or response JSON for the bridge.
2. CSRF protects cookie-mutating bridge POSTs (same pattern as remember-me / session).
3. Classic web logout clears API cookies and best-effort revokes refresh/access.
4. Dual stacks remain: web session for dashboards; API cookies for future same-origin API calls (Phase 2+).

---

## Verify

```bash
composer frontend-check
composer auth-lifecycle-check
vendor/bin/phpunit --filter FrontendApiAuth
vendor/bin/phpunit --filter AuthLifecycle
```

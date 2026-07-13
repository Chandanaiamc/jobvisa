# Authentication Middleware

## Pipeline

`App\Core\Router` runs middleware tags via `JobVisa\App\Http\MiddlewarePipeline` before the controller action. Aliases are defined in `config/middleware.php`.

| Alias | Class | Behaviour |
|---|---|---|
| `web` | `StartSessionMiddleware` | Ensure HTTP session started |
| `remember` | `RememberMeMiddleware` | Restore session from remember cookie when valid |
| `csrf` | `CsrfMiddleware` | Validate `_token` / `X-CSRF-TOKEN` on unsafe methods |
| `auth` | `AuthenticateMiddleware` | Require authenticated session (401 JSON) |
| `guest` | `GuestMiddleware` | Reject if already authenticated (403 JSON) |

## Route group usage

Defined in `routes/auth.php` (parent `auth` group in `config/routing.php` has empty default middleware so nested groups own their stacks).

Existing public `web` / `health` routes keep the `web` tag only (session start) — URLs unchanged.

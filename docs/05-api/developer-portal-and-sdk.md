# Developer Portal & SDK Foundation (Sprint 4.6)

**Project:** JobVisa.lk Enterprise  
**Rules version:** `4.6.0`  
**Status:** Implemented  
**Depends on:** Sprint 4.5 Enterprise API Platform

---

## Goal

Give integrators a first-class developer experience: browsable docs, token management UI, OpenAPI access, and a PHP SDK foundation — without changing web product modules or CSRF rules.

---

## Architecture

| Piece | Location |
|---|---|
| Portal service | `app/Domain/Api/Portal/` |
| Portal UI | `/developers/*` + `app/views/developers/` |
| In-app SDK client | `app/Domain/Api/Sdk/JobVisaClient.php` |
| Standalone SDK | `sdk/php/` |
| Portal status API | `GET /api/v1/portal` |
| Config | `config/developer_portal.php` |

No new database migration — reuses Sprint 4.5 personal access tokens.

---

## Portal pages

| Path | Access |
|---|---|
| `/developers` | Public overview |
| `/developers/getting-started` | Public |
| `/developers/authentication` | Public |
| `/developers/endpoints` | Public |
| `/developers/errors` | Public |
| `/developers/webhooks` | Public |
| `/developers/sdk` | Public |
| `/developers/openapi` | Public |
| `/developers/tokens` | Session auth + CSRF |

---

## SDK foundation

- Envelope-aware `get` / `post` / `health`
- Bearer token support
- `X-Request-Id` generation
- Parses `request_id` from JSON responses

---

## Environment

| Variable | Default | Purpose |
|---|---|---|
| `DEV_PORTAL_ENABLED` | `true` | Toggle portal HTML |
| `DEV_SDK_ENABLED` | `true` | Show SDK guidance |
| `DEV_PORTAL_TRY_IT` | `true` | Reserved for interactive try-it |
| `DEV_PORTAL_API_BASE` | _(derived from APP_URL)_ | Override API base shown in docs |

---

## Security

- Token create/revoke on portal uses **session auth + CSRF** (unchanged web model)
- Bearer API auth remains CSRF-exempt
- Raw tokens shown once; never logged by portal flash beyond one-time display
- Existing AI / auth / deploy modules untouched

---

## Verification

```bash
E:\localhost\php\php.exe scripts/api-portal-check.php
```

Expect final line: `PASS`

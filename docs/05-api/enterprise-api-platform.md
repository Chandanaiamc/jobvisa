# Enterprise API Platform (Sprint 4.5)

**Project:** JobVisa.lk Enterprise  
**Rules version:** `4.5.0`  
**Status:** Implemented  
**OpenAPI:** `docs/05-api/openapi.json`

---

## Architecture

Versioned JSON API under `/api/v1` on the existing `api` route group. Web routes, CSRF, session auth, and AI modules are unchanged.

| Layer | Responsibility |
|---|---|
| `ApiMiddleware` | CORS, JSON content-type, IP rate limit, audit, API exception → JSON |
| `ApiAuthenticateMiddleware` | Bearer personal access tokens (no CSRF) |
| `ApiRoleMiddleware` | Role gates (`api.employer`, `api.jobseeker`, `api.admin`) |
| Domain services | Tokens, rate limit store, audit, webhooks |
| `ApiResource` | Output filtering (no secrets / excess PII) |

Future `/api/v2` can be added as another nested group beside `v1`.

---

## Versioning

- Current: **v1** (`JobVisa\App\Domain\Api\Support\ApiVersion`)
- Routes: `/api/v1/...`
- Docs probe: `GET /api/v1/docs/openapi`

---

## Authentication

Personal access tokens:

- Prefix `jv1_` (configurable)
- Only **HMAC-SHA256 hash** stored (`token_hash`)
- Name / device label, expiration, revocation, last-used + IP + UA
- Header: `Authorization: Bearer <token>`

Create: `POST /api/v1/tokens` (authenticated) or `PersonalAccessTokenService::create()`  
Revoke: `POST /api/v1/tokens/{id}/revoke`

---

## Authorization

- Deny by default on protected routes
- Reuses user `role` slugs: `seeker`, `employer`, `admin` / `super_admin` / `staff`
- Ownership enforced via existing repositories/policies (resume user_id, employer job ownership)
- IDOR: foreign resume/job → generic `404`

---

## Rate limiting

- Drivers: `file` now; `redis` reserved
- Buckets: per IP, per user, per token
- Headers: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`, `Retry-After`
- HTTP **429** on exceed

---

## Response format

Success:

```json
{ "success": true, "data": {}, "meta": {}, "request_id": "" }
```

Error:

```json
{ "success": false, "error": { "code": "", "message": "", "details": {} }, "request_id": "" }
```

---

## Endpoints

### Public
| Method | Path |
|---|---|
| GET | `/api/v1/health` |
| GET | `/api/v1/jobs` |
| GET | `/api/v1/jobs/{job}` |
| GET | `/api/v1/docs/openapi` |

### Authenticated
| Method | Path |
|---|---|
| GET | `/api/v1/me` |
| GET | `/api/v1/resumes` |
| GET | `/api/v1/resumes/{resume}` |
| GET | `/api/v1/resumes/{resume}/intelligence` |
| GET | `/api/v1/jobs/{job}/match?resume_id=` |
| GET/POST | `/api/v1/tokens` |
| POST | `/api/v1/tokens/{id}/revoke` |

### Employer
| Method | Path |
|---|---|
| GET | `/api/v1/employer/jobs` |
| GET | `/api/v1/employer/jobs/{job}/applicants` |
| GET | `/api/v1/employer/jobs/{job}/ranking` |

---

## Error codes

| Code | HTTP |
|---|---|
| `unauthorized` | 401 |
| `token_expired` / `token_revoked` | 401 |
| `forbidden` | 403 |
| `not_found` | 404 |
| `validation_error` | 422 |
| `rate_limited` | 429 |
| `server_error` | 500 |

---

## Webhooks foundation

Events: `job.applied`, `resume.updated`, `interview.completed`, `offer.evaluated`  

Tables: `api_webhook_subscriptions`, `api_webhook_deliveries`  
HMAC header: `X-JobVisa-Signature: sha256=<hmac>`  
**Disabled by default** (`API_WEBHOOKS_ENABLED=false`) until configured.

---

## Security

- CORS allow-list (`API_CORS_ORIGINS`); local permissive when empty
- HTTPS-ready via existing `ForceHttpsMiddleware`
- Token hashing; never log raw tokens/passwords
- No CSRF on bearer API; web CSRF unchanged
- Output filtering via `ApiResource`

---

## Migration

`064_create_api_platform_tables.sql` — **apply only after approval**.

```bash
# After approval
php scripts/deploy.php --run --confirm=DEPLOY
# or apply 064 SQL manually
```

---

## Testing

```bash
E:\localhost\php\php.exe scripts/api-check.php
```

Expect final line: `PASS`

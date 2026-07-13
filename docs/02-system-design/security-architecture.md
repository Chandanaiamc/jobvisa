# Security Architecture

**Project:** JobVisa.lk Enterprise  
**Status:** Active — see also `enterprise-security-hardening.md` (Sprint 4.7)

## Layers

1. **Transport** — `ForceHttpsMiddleware`, HSTS (optional), trusted proxies  
2. **HTTP hardening** — `SecurityHeadersMiddleware` (CSP, frame options, nosniff, COOP)  
3. **Session** — strict cookies, regeneration on login/logout  
4. **CSRF** — `CsrfMiddleware` on mutating web routes (not bearer API)  
5. **AuthN/Z** — `AuthManager`, role middleware, domain policies  
6. **API** — personal access tokens, rate limits, CORS allow-list (Sprint 4.5)  
7. **Audit** — `Logger::security` + `SecurityAuditLogger` → `audit_logs`

## Related docs

- `security-foundation-implementation.md`
- `enterprise-security-hardening.md`
- `docs/05-api/enterprise-api-platform.md`
- `docs/08-deployment/production-readiness.md`

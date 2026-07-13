# Enterprise Security Hardening & OWASP Compliance (Sprint 4.7)

**Project:** JobVisa.lk Enterprise  
**Rules version:** `4.7.0`  
**Status:** Implemented  
**Migration:** None (reuses `audit_logs` from 022)

---

## Goal

Harden JobVisa.lk against OWASP Top 10 risks without breaking web, API, or AI modules. Prefer config-driven controls and fail-safe audit logging.

---

## Deliverables

| Component | Role |
|---|---|
| `PasswordPolicy` | Configurable password rules (default min length 8) |
| `SecurityAuditLogger` | Writes to `audit_logs` + `Logger::security` |
| `SecurityHardeningService` | Readiness status / OWASP map |
| Config-driven CSP | `security.csp_*` (compatible defaults) |
| Trusted proxies | IP / HTTPS forwarding gated by `TRUSTED_PROXIES` |
| CSRF key wiring | `security.csrf_token_key` |
| Rate-limit flag | `security.rate_limit_enabled` gates session RateLimiter |
| Expanded log redaction | secrets, bearer, APP_KEY, plain tokens |
| APP_KEY production guard | Critical when missing in prod/staging |
| Optional remember pepper | `REMEMBER_PEPPER_ENABLED` (default off) |

---

## OWASP Top 10 (2021) mapping

| ID | Control |
|---|---|
| A01 Broken Access Control | Existing role/policy middleware; trusted proxy IP handling |
| A02 Cryptographic Failures | Argon2id; APP_KEY guard; optional remember pepper |
| A03 Injection | PDO prepared statements; CSP config |
| A04 Insecure Design | PasswordPolicy on register/reset |
| A05 Security Misconfiguration | Headers + CSP config + security-check CLI |
| A06 Vulnerable Components | Document `composer audit` (ops) |
| A07 Identification Failures | Login throttle; password policy; remember config |
| A08 Software/Data Integrity | Out of scope (doc) |
| A09 Logging Failures | SecurityAuditLogger + CSRF reject events |
| A10 SSRF | Low surface (doc) |

---

## Environment flags

| Variable | Default | Purpose |
|---|---|---|
| `PASSWORD_MIN_LENGTH` | `8` | Minimum password length |
| `PASSWORD_REQUIRE_MIXED` | `false` | Upper + lower |
| `PASSWORD_REQUIRE_NUMBER` | `false` | Digit required |
| `PASSWORD_REQUIRE_SYMBOL` | `false` | Symbol required |
| `PASSWORD_ALGO` | `argon2id` | Hash algorithm preference |
| `CSP_ENABLED` | `true` | Emit Content-Security-Policy |
| `CSP_POLICY` | _(safe default)_ | Full CSP string |
| `SECURITY_AUDIT_ENABLED` | `true` | Write `audit_logs` |
| `REMEMBER_PEPPER_ENABLED` | `false` | HMAC pepper for remember-me |
| `RATE_LIMIT_ENABLED` | `true` | Session RateLimiter gate |
| `CSRF_TOKEN_KEY` | `_csrf_token` | Session CSRF key |
| `APP_KEY` | _(required in prod)_ | Secret derivation |
| `TRUSTED_PROXIES` | _(empty)_ | Comma-separated proxy IPs |

---

## Non-goals (intentionally)

- Removing CSP `'unsafe-inline'` without nonce rollout (would break auth/developer CSS/JS)
- Forcing `SESSION_SECURE=true` on local
- Changing API bearer CSRF exemption
- New schema migrations

---

## Verification

```bash
E:\localhost\php\php.exe scripts/security-check.php
```

Expect final line: `PASS`

# Authentication Endpoints

All responses are JSON. No HTML views are rendered by these controllers.

Base (local): `http://localhost/jobvisa/public`

## CSRF bootstrap

| Method | Path | Middleware | Purpose |
|---|---|---|---|
| GET | `/auth/csrf` | web, remember | Issue CSRF token |

## Guest (unauthenticated)

| Method | Path | Middleware | Purpose |
|---|---|---|---|
| POST | `/auth/register` | web, remember, guest, csrf | Register seeker/employer |
| POST | `/auth/login` | web, remember, guest, csrf | Login + optional remember |

## Token workflows (guest or authenticated)

| Method | Path | Middleware | Purpose |
|---|---|---|---|
| POST | `/auth/password/forgot` | web, remember, csrf | Request reset token |
| POST | `/auth/password/reset` | web, remember, csrf | Confirm new password |
| POST | `/auth/email/verify` | web, remember, csrf | Verify email token |
| POST | `/auth/email/resend` | web, remember, csrf | Resend verification |

## Authenticated

| Method | Path | Middleware | Purpose |
|---|---|---|---|
| GET | `/auth/me` | web, remember, auth | Current user + dashboard redirect |
| GET | `/auth/redirect` | web, remember, auth | Role dashboard target only |
| POST | `/auth/logout` | web, remember, auth, csrf | Logout + clear remember cookie |

## Example bodies

**Register**

```json
{
  "_token": "...",
  "full_name": "Jane Seeker",
  "email": "jane@example.com",
  "password": "SecretPass!123",
  "password_confirmation": "SecretPass!123",
  "role": "seeker"
}
```

**Login**

```json
{
  "_token": "...",
  "email": "jane@example.com",
  "password": "SecretPass!123",
  "remember": true
}
```

Successful login includes `redirect.path` such as `/jobseeker/dashboard`, `/employer/dashboard`, or `/admin/dashboard`.

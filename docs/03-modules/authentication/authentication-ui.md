# Authentication UI (Sprint 2A)

**Scope:** Registration, Login, Logout HTML UX  
**Out of scope:** Forgot-password pages, email-verification pages, full dashboards  

---

## Routes

| Method | Path | Controller | Middleware |
|---|---|---|---|
| GET | `/register` | `WebAuthController@showRegister` | web, remember, guest.web, csrf |
| POST | `/register` | `WebAuthController@register` | web, remember, guest.web, csrf |
| GET | `/login` | `WebAuthController@showLogin` | web, remember, guest.web, csrf |
| POST | `/login` | `WebAuthController@login` | web, remember, guest.web, csrf |
| POST | `/logout` | `WebAuthController@logout` | web, remember, auth.web, csrf |
| GET | `/admin` | `PortalController@admin` | web, remember, auth.web, admin |
| GET | `/employer` | `PortalController@employer` | web, remember, auth.web, employer |
| GET | `/jobseeker` | `PortalController@jobseeker` | web, remember, auth.web, jobseeker |

JSON API routes under `/auth/*` from Sprint 1 remain unchanged.

`GET /logout` is **not** registered.

---

## Controllers

| Class | Role |
|---|---|
| `App\Controllers\Auth\WebAuthController` | HTML register/login/logout |
| `App\Controllers\PortalController` | Protected placeholders |
| `App\Controllers\Auth\AuthController` | Existing JSON API (unchanged contract) |

`WebAuthController` **reuses** `RegistrationService`, `AuthManager`, `RememberMeCookie`, `DashboardRedirector` — it does not replace them.

---

## Views

| Path | Purpose |
|---|---|
| `app/views/auth/layout.php` | Auth shell |
| `app/views/auth/partials/header.php` | Header / brand |
| `app/views/auth/partials/footer.php` | Footer |
| `app/views/auth/partials/flash.php` | Flash + form-level errors |
| `app/views/auth/register.php` | Register page entry |
| `app/views/auth/register-form.php` | Register fields |
| `app/views/auth/login.php` | Login page entry |
| `app/views/auth/login-form.php` | Login fields |
| `app/views/portal/placeholder.php` | Role placeholder |
| `public/assets/css/auth.css` | Auth styles |
| `public/assets/js/auth.js` | Password show/hide |

---

## Validation rules

### Registration (web layer + `RegistrationService`)

- `first_name` required, max 80  
- `last_name` required, max 80  
- `email` required, email, unique (service)  
- `phone` optional, max 32  
- `role` required, `seeker|employer`  
- `password` required, min 8, confirmed  
- `terms` required (checkbox)  
- Combined `full_name` passed to `RegistrationService`  
- Passwords never re-rendered into the form  

### Login

- `email` required, email  
- `password` required  
- Failures show generic “Invalid email or password.” (or throttle message)  

---

## Flows

### Registration

1. Guest opens `/register`  
2. CSRF field rendered  
3. POST validated → `RegistrationService::register(..., autoLogin: true)`  
4. Password hashed via existing `PasswordHasher`  
5. Session established (regenerated)  
6. Flash success → redirect by role  

### Login

1. Guest opens `/login`  
2. POST → `AuthManager::attempt` (throttle + attempt logging + regenerate)  
3. Optional remember-me cookie via `RememberMeCookie`  
4. Flash success → role redirect  

### Logout

1. Authenticated POST `/logout` with CSRF  
2. `AuthManager::logout` + remember cookie forget  
3. Flash success → `/login`  

---

## Role redirects

Configured in `config/auth.php`:

| Role | Path |
|---|---|
| admin / staff | `/admin` |
| employer | `/employer` |
| seeker | `/jobseeker` |

Placeholders only — full dashboards later.

---

## Security controls

- Output escaping via `e()`  
- CSRF on all mutating auth UI routes  
- Prepared statements in Auth repositories/services  
- Argon2id/bcrypt hashing  
- Session regeneration on login/logout  
- Login throttling + `login_attempts` tracking  
- No passwords in views/logs (Logger redacts)  
- Logout POST-only  

---

## Testing steps

1. Open `/register` and `/login` as guest — forms render  
2. Submit register with mismatched passwords — field errors, values preserved (no passwords)  
3. Register valid seeker — lands on `/jobseeker` placeholder  
4. Logout via portal form — lands on `/login` with success flash  
5. Login with wrong password — generic error  
6. Login as admin seed user — lands on `/admin`  
7. Visit `/register` while logged in — redirected away  
8. Confirm `/`, `/about`, `/health/database`, `/health/container` still work  
9. Confirm `GET /logout` is not a valid logout action  

---

*End of authentication UI documentation.*

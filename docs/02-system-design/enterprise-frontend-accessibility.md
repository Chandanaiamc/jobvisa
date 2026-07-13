# Enterprise Frontend Polish & Accessibility (Sprint 4.8)

**Project:** JobVisa.lk Enterprise  
**Rules version:** `4.8.0`  
**Status:** Implemented  
**Migration:** None

---

## Goal

Add a shared accessibility foundation across primary UI shells without redesigning product views or brand systems (Fraunces/Outfit dashboards, Syne/DM Sans developers).

---

## Deliverables

| Component | Role |
|---|---|
| `public/assets/css/a11y.css` | Skip-link styles, `:focus-visible`, `.sr-only`, `prefers-reduced-motion` |
| `public/assets/js/a11y.js` | Focus `#main` after skip-link activation |
| `app/views/partials/skip-link.php` | “Skip to main content” → `#main` |
| Layout hooks | Auth, jobseeker, employer, developers, portal placeholder |
| `FrontendPolishService` | Readiness status / WCAG map |
| `config/frontend.php` | Feature flags |
| `scripts/frontend-check.php` | CLI gate → `PASS` |

Also fixed: developer portal CSS resolves via `asset('css/developers.css')` under `public/assets/css/`.

---

## Layouts updated

| Layout | Skip link | `#main` | a11y assets |
|---|---|---|---|
| `auth/layout.php` | yes | yes | yes |
| `jobseeker/layout.php` | yes | yes | yes |
| `employer/layout.php` | yes | yes | yes |
| `developers/layout.php` | yes | yes | yes |
| `portal/placeholder.php` | yes | yes | yes |

---

## WCAG 2.2 mapping (foundation)

| Principle | Control |
|---|---|
| Perceivable | Existing contrast tokens; shared focus ring |
| Operable | Skip link, keyboard focus-visible, reduced motion |
| Understandable | Existing form labels / flash errors |
| Robust | `lang="en"`, landmarks, `#main` target |

This sprint is **foundation polish**, not a full WCAG audit of every screen.

---

## Environment flags

| Variable | Default | Purpose |
|---|---|---|
| `FRONTEND_A11Y_ENABLED` | `true` | Master switch |
| `FRONTEND_SKIP_LINK` | `true` | Render skip link partial |
| `FRONTEND_FOCUS_VISIBLE` | `true` | Documented intent (CSS always ships when linked) |
| `FRONTEND_REDUCED_MOTION` | `true` | Documented intent (CSS media query) |

---

## Verify

```bash
composer frontend-check
# or: php scripts/frontend-check.php
```

Expect final line: `PASS`.

---

## Out of scope

- Full visual redesign or new design system
- Per-page ARIA rewrites of every form
- Automated axe/Lighthouse CI (future)
- Database migrations

# Changelog

All notable changes to **JobVisa.lk Enterprise Platform** are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] — 2026-07-13

### Added

#### Platform foundation
- Custom PHP 8.2+ MVC enterprise stack (DTO / Repository / Service / Policy / Controller / views)
- Authentication, registration, email verification, password reset, role-based portals
- Job seeker profile, resumes, CV, and dashboard surfaces
- Employer AI assistant surfaces (recruiter, interview, ranking)

#### AI & career intelligence (Series 2F–3.x)
- Resume intelligence, job matching, applicant ranking
- Recruiter assistant, interview assistant, career coach
- Resume builder, cover letter, application assistant
- Salary intelligence, skill gap, learning path
- Portfolio builder, mock interview, job search copilot, offer evaluation

#### Enterprise operations (Series 4.x)
- **4.1** Production readiness — health live/ready, maintenance, HTTPS/security headers
- **4.2** Performance — file cache, pagination, query profiler, timing
- **4.3** Observability — request IDs, metrics, error ring buffer, probes
- **4.4** Deployment — deploy CLI, backups, migrations, rollback, release stamps
- **4.5** Enterprise API — `/api/v1`, PATs, rate limits, CORS, OpenAPI, webhooks foundation
- **4.6** Developer portal & PHP SDK — `/developers/*`, token UI, `JobVisaClient`
- **4.7** Security hardening — CSP, password policy, security audit logger, trusted proxies
- **4.8** Frontend polish & accessibility — skip links, focus-visible, reduced motion
- **4.9** Testing & Release Candidate — PHPUnit suites, QA gate runner, RC checklist

#### Release package
- Root `VERSION`, `CHANGELOG.md`, `RELEASE_NOTES.md`, `LICENSE`
- `release/manifest.json` enterprise release manifest
- `enterprise-release-check` production verification CLI

### Security
- CSRF protection, Argon2id password hashing, XSS helpers
- Configurable CSP and OWASP-oriented hardening controls
- API bearer tokens stored hashed; session CSRF unchanged for browser flows

### Documentation
- System design docs for production, performance, observability, deployment, API, portal, security, a11y, and RC testing

[1.0.0]: https://jobvisa.lk/releases/v1.0.0

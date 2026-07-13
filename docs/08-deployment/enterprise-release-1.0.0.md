# Enterprise Release 1.0.0 (GA)

**Product:** JobVisa.lk  
**Version:** `1.0.0` (`v1.0.0`)  
**Release date:** 2026-07-13  
**Vendor:** Readleaf (Pvt) Ltd  
**License:** Proprietary (`LICENSE`)

---

## Goal

Package JobVisa.lk as an enterprise **General Availability** release with immutable release artifacts, a signed-off manifest, and a final production verification CLI.

---

## Artifacts

| Path | Purpose |
|---|---|
| `VERSION` | Canonical tag (`v1.0.0`) |
| `CHANGELOG.md` | Keep-a-Changelog history |
| `RELEASE_NOTES.md` | Stakeholder release notes |
| `LICENSE` | Proprietary license |
| `release/manifest.json` | Machine-readable release manifest + checksums |
| `storage/releases/CURRENT` | Runtime stamp (`1.0.0`) after verification |

---

## Domain

| Component | Role |
|---|---|
| `EnterpriseReleaseVersion` | `1.0.0` / `v1.0.0` |
| `ReleaseManifestBuilder` | Builds/writes `release/manifest.json` |
| `EnterpriseReleaseService` | Artifact + module verification, stamp |
| `config/release.php` | Flags (`ENTERPRISE_RELEASE_*`) |
| `scripts/enterprise-release-check.php` | Final gate → `PASS` / `FAIL` |

---

## Module compatibility (must remain)

| Module | Rules version |
|---|---|
| Production | 4.1.0 |
| Performance | 4.2.0 |
| Observability | 4.3.0 |
| Deployment | 4.4.0 |
| API | 4.5.0 |
| Developer portal | 4.6.0 |
| Security | 4.7.0 |
| Frontend a11y | 4.8.0 |
| Testing / RC | 4.9.0 |
| Enterprise release | 1.0.0 |

---

## Verify

```bash
composer enterprise-release-check
# or: php scripts/enterprise-release-check.php
```

Expect final line: **`PASS`**.

This gate refreshes the manifest, validates artifacts, checks production live/ready, runs enterprise smoke, optionally runs `release-candidate-check`, and stamps `1.0.0`.

---

## Environment flags

| Variable | Default | Purpose |
|---|---|---|
| `ENTERPRISE_RELEASE_ENABLED` | `true` | Master switch |
| `ENTERPRISE_RELEASE_RUN_RC` | `true` | Invoke RC gate inside release check |
| `ENTERPRISE_RELEASE_STAMP` | `true` | Write `storage/releases/CURRENT` |
| `APP_VERSION` | `1.0.0` | `config('app.version')` |
| `APP_VERSION_TAG` | `v1.0.0` | `config('app.version_tag')` |

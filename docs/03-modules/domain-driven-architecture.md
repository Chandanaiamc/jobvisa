# Domain-Driven Architecture

**Project:** JobVisa.lk  
**Layer:** `app/Domain` (`JobVisa\App\Domain\*`)  
**Status:** Foundation scaffolding only — no business logic wired  

---

## Domain-Driven Design overview

JobVisa.lk organizes core business concepts into **bounded contexts** (domains). Each domain owns its language, invariants, and future use-cases, independent of HTTP controllers, views, and SQL drivers.

This foundation introduces the folder and type structure so modules can grow without coupling presentation to persistence.

```text
HTTP / Routes / Controllers   →  Application edge (unchanged)
Domain (Entities, Services…)  →  Business language & contracts
Infrastructure (future)       →  PDO repositories, mail, gateways
```

Domains in this release:

| Domain | Namespace root |
|---|---|
| User | `JobVisa\App\Domain\User` |
| Employer | `JobVisa\App\Domain\Employer` |
| Company | `JobVisa\App\Domain\Company` |
| Job | `JobVisa\App\Domain\Job` |
| Resume | `JobVisa\App\Domain\Resume` |
| Application | `JobVisa\App\Domain\Application` |
| Payment | `JobVisa\App\Domain\Payment` |
| Notification | `JobVisa\App\Domain\Notification` |

Shared contracts and bases live under:

- `JobVisa\App\Domain\Contracts`
- `JobVisa\App\Domain\Support`

---

## Layer responsibilities

| Layer / folder | Responsibility |
|---|---|
| **Entities** | Aggregate roots and identity (`EntityInterface` / `Support\Entity`) |
| **Repositories** | Persistence contracts only (`*RepositoryInterface`) — no SQL here |
| **Services** | Future use-case orchestration (`AbstractDomainService`) |
| **DTO** | Cross-boundary data shapes (`DataTransferObject`) |
| **Validators** | Domain input rules (`AbstractValidator`) — shell returns valid |
| **Policies** | Authorization decisions (`AbstractPolicy`) — denies by default |
| **Events** | Domain facts for later listeners (`DomainEvent`) |
| **Exceptions** | Domain failure types (`DomainException`) |

**Not in Domain:** routes, views, CSRF, session auth (`JobVisa\App\Auth` stays separate), controllers.

---

## Repository Pattern

- Domain defines **interfaces** (e.g. `UserRepositoryInterface`).
- Implementations will later live in infrastructure (PDO / query builders) and bind via the DI container.
- Controllers and services depend on interfaces, not tables.
- Existing `JobVisa\App\Auth\UserRepository` remains the auth data access path until an intentional migration; this Domain User repository is a parallel contract, not a replacement.

```text
Service → UserRepositoryInterface → (future) PdoUserRepository → Database
```

---

## Service Layer

- `*Service` classes extend `AbstractDomainService`.
- Intended role: coordinate validators, repositories, policies, and events for a use-case.
- **No use-cases are implemented in this foundation** — empty shells only.
- Keep HTTP (request/response) out of domain services; accept DTOs / primitives instead.

---

## DTO usage

- DTOs extend `Support\DataTransferObject` with `fromArray()` / `toArray()`.
- Use DTOs at boundaries: controller → service, service → API response mapping, queue payloads.
- Do not put validation rules or persistence inside DTOs.
- Current `*Data` classes are empty shells ready for typed properties later.

---

## Event architecture

```text
Domain action succeeds
  → new concrete DomainEvent
  → EventDispatcherInterface::dispatch()
  → listeners (notify, audit, search index, …)
```

- `DomainEventInterface` + `Support\DomainEvent` define the event shape.
- `EventDispatcherInterface` is declared but **not registered** in providers yet.
- Each domain has a placeholder `*DomainEvent` (`domain.user`, `domain.job`, …).
- Prefer specific events later (`JobPublished`, `ApplicationSubmitted`) over the placeholder.

---

## Future scalability

1. **Implement** repository adapters under a dedicated infrastructure namespace; bind in service providers.  
2. **Add** real validators/policies per role (employer, jobseeker, admin).  
3. **Wire** a sync then async event dispatcher (queue workers).  
4. **Version** API DTOs separately from domain entities (`/api/v1`).  
5. **Split** large domains into submodules if language diverges (e.g. Payment → Invoice, Payout).  
6. **Keep** Auth foundation (`AuthManager`) as the security edge; Domain User models identity/profile concerns, not login sessions.

---

## Autoloading

PSR-4 already maps `JobVisa\App\` → `app/`. No `composer.json` change is required for `app/Domain`.

If Composer’s optimized classmap is used in production, run `composer dump-autoload` after deploy (optional for the fallback autoloader).

---

## Integration notes

- Routes, UI, authentication, and SQL were **not** modified for this foundation.
- Domain types are **not** bound in `config/providers.php` yet — resolve only when a feature needs them.
- Do not confuse `App\Core\Model` / `BaseModel` with Domain entities; migrate gradually.
- Policies currently **deny all** — do not call them for live authorization until rules exist.

---

## Testing checklist

- [ ] Public pages still load (`/`, `/about`, `/jobs`, `/companies`, `/contact`)
- [ ] Health endpoints unchanged (`/health/database`, `/health/container`)
- [ ] Auth behaviour unchanged (no new login/register wiring)
- [ ] Autoload resolves `JobVisa\App\Domain\User\Entities\User` (and peers)
- [ ] No route or view files changed unintentionally
- [ ] Domain classes contain no SQL / PDO calls

---

*End of domain-driven architecture documentation.*

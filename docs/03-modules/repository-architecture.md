# Repository Architecture

**Project:** JobVisa.lk  
**Status:** Active — additive; Auth / routes / schema unchanged  

---

## Overview

The enterprise repository layer isolates SQL behind interfaces and concrete PDO repositories. Controllers and domain services should depend on abstractions (`*RepositoryInterface`), not on `Database::query` or table details.

```text
Domain / Application code
        │
        ▼
Repository Interface (Domain or Infrastructure)
        │
        ▼
Concrete Repository (JobVisa\App\Repositories\*)
        │
        ▼
PDO via BaseRepository → App\Core\Database
```

---

## SOLID alignment

| Principle | How applied |
|---|---|
| **S**ingle responsibility | One repository per aggregate/table concern |
| **O**pen/closed | New queries via new methods/classes; base helpers stay stable |
| **L**iskov | Implementations honour Domain + Infrastructure contracts |
| **I**nterface segregation | Narrow contracts (`findBySlug`, `findPublished`, …) |
| **D**ependency inversion | Container binds interfaces → concrete classes |

---

## Repository Pattern

- **Domain contracts** (`JobVisa\App\Domain\*\Repositories\*RepositoryInterface`) return entities (`findById(): ?Entity`).
- **Infrastructure contracts** (`JobVisa\App\Repositories\Contracts\*`) return row arrays and query helpers.
- **Concrete classes** implement **both** (same singleton instance in the container).
- **`BaseRepository`** owns shared PDO fetch helpers only — no business rules.

`JobVisa\App\Auth\UserRepository` remains the authentication data access class.  
`JobVisa\App\Repositories\UserRepository` is a separate enterprise repository and must not replace Auth bindings.

---

## Classes

| Class | Role |
|---|---|
| `Repositories\BaseRepository` | PDO helpers |
| `Repositories\UserRepository` | `users` (respects `deleted_at`) |
| `Repositories\CompanyRepository` | `companies` |
| `Repositories\JobRepository` | `jobs` |
| `Repositories\ApplicationRepository` | `applications` |
| `Providers\RepositoryServiceProvider` | Container registration |

Entity hydration uses `Entity::reconstitute($id)` on Domain entity shells.

---

## Container bindings

Registered by `RepositoryServiceProvider` (after `DatabaseServiceProvider`, before `AuthServiceProvider`):

- `PDO`
- Concrete: `UserRepository`, `CompanyRepository`, `JobRepository`, `ApplicationRepository`
- Infrastructure interfaces → same concretes
- Domain interfaces → same concretes

Auth continues to bind `JobVisa\App\Auth\UserRepository` independently.

---

## Usage (future)

```php
use JobVisa\App\Domain\Job\Repositories\JobRepositoryInterface;

$jobs = container(JobRepositoryInterface::class);
$job = $jobs->findById(1); // ?Job entity

use JobVisa\App\Repositories\Contracts\JobRepositoryInterface as JobRows;

$rows = container(JobRows::class)->findPublished(20);
```

Do not inject repositories into Auth flows unless an explicit migration of Auth is planned.

---

## Out of scope (this release)

- Write/update/delete APIs beyond reads  
- Changing Auth login/password/remember logic  
- Route or UI wiring  
- Schema migrations  

---

## Testing checklist

- [ ] Public pages load  
- [ ] `/health/database`, `/health/container` OK  
- [ ] Auth services still resolve (`Auth\UserRepository`, `AuthManager`, …)  
- [ ] Domain + infrastructure repository interfaces resolve from container  
- [ ] `Auth\UserRepository` !== `Repositories\UserRepository`  
- [ ] No route/controller/view auth behaviour changes  

---

*End of repository architecture documentation.*

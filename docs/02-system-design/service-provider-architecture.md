# Service Provider Architecture

**Project:** JobVisa.lk Enterprise  
**Scope:** Bootstrap modularization via service providers  
**Constraints:** No auth features, no schema changes, no route/UI changes  

---

## Overview

Service Providers package framework bindings and startup side-effects into focused classes. The Dependency Injection `Container` stays generic; providers teach it how to build JobVisa services.

Configured in `config/providers.php` and loaded automatically from `bootstrap/app.php`.

---

## Provider lifecycle

```text
1. Autoload + helpers
2. Legacy App\Core\Config env + config load
3. Create empty Container
4. ProviderManager::register()  → each provider->register()
5. ProviderManager::boot()      → each provider->boot()
6. Create App\Core\App and return
7. Later: App::run() loads routes (unchanged)
```

### `register()`

- Bind classes into the container (`bind`, `singleton`, `instance`).
- Must not depend on other providers’ bindings being fully usable unless ordered carefully.
- Prefer **no I/O** (no DB connect, no session start) here.

### `boot()`

- Runs only after **all** `register()` calls complete.
- Safe to `$this->container->get(...)`.
- Place side effects here (exception handler, session start, timezone).

---

## Registration

Providers are listed in order in `config/providers.php`:

1. `AppServiceProvider` — Config + timezone  
2. `LoggingServiceProvider` — Logger  
3. `SessionServiceProvider` — SessionManager + session start  
4. `SecurityServiceProvider` — Csrf, SecurityHelper, ExceptionHandler  
5. `DatabaseServiceProvider` — lazy `App\Core\Database` binding  
6. `ViewServiceProvider` — `App\Core\View`  
7. `RouteServiceProvider` — `Router` + `RouteRegistrar`; `App::run()` calls `loadRoutes()` (see `docs/05-api/routing-architecture.md`)  

To add a module later:

```php
// config/providers.php
return [
    // ...
    JobVisa\App\Providers\AuthServiceProvider::class, // future
];
```

---

## Boot process

`JobVisa\App\Providers\ProviderManager`:

1. Instantiates each provider with the shared `Container`.
2. Calls `register()` sequentially.
3. Calls `boot()` sequentially.
4. Guards against double register/boot.

Session start and exception handler registration moved from inline bootstrap into provider `boot()` methods so startup remains centralized.

---

## Dependency Injection

Providers receive `Container` via constructor (`ServiceProvider`).

Examples:

```php
// Inside register()
$this->container->singleton(Config::class, function () {
    $config = new Config(base_path('config'));
    $config->loadAll();
    return $config;
});

// Later resolution
$config = container(Config::class);
$view = container(\App\Core\View::class);
```

Constructor autowiring in `Container` continues to work for future services that type-hint bindings registered by providers.

---

## Future extension

| Provider (future) | Responsibility |
|---|---|
| `AuthServiceProvider` | Guards, password hasher, auth services |
| `MailServiceProvider` | Mailer + queue bridge |
| `CacheServiceProvider` | File/Redis cache |
| `EventServiceProvider` | Listener registration |
| `ApiServiceProvider` | API rate limiters / response factories |

Keep feature providers out of the HTTP layer; controllers resolve services from the container.

---

## Classes

| Class | Path |
|---|---|
| `ServiceProvider` | `app/Providers/ServiceProvider.php` |
| `ProviderManager` | `app/Providers/ProviderManager.php` |
| `AppServiceProvider` | `app/Providers/AppServiceProvider.php` |
| `DatabaseServiceProvider` | `app/Providers/DatabaseServiceProvider.php` |
| `RouteServiceProvider` | `app/Providers/RouteServiceProvider.php` |
| `ViewServiceProvider` | `app/Providers/ViewServiceProvider.php` |
| `SecurityServiceProvider` | `app/Providers/SecurityServiceProvider.php` |
| `LoggingServiceProvider` | `app/Providers/LoggingServiceProvider.php` |
| `SessionServiceProvider` | `app/Providers/SessionServiceProvider.php` |

---

## Testing instructions

1. Open any existing page (`/public/`, `/public/about`) — must render as before.  
2. Open `/public/health/container` (local/debug) — Container/Config/Singleton statuses still pass.  
3. Confirm session cookie (`SESSION_NAME`) is still issued.  
4. Trigger a controlled exception in a throwaway local script with `APP_DEBUG=true` — ExceptionHandler still active.  
5. Confirm `config/providers.php` lists all seven feature providers plus load order.

---

*No Composer or SQL execution is required for this architecture.*

# Config Manager & Dependency Injection Container

**Project:** JobVisa.lk Enterprise  
**Scope:** Foundation only — no authentication features  

---

## Classes created

| Class | Path | Responsibility |
|---|---|---|
| `JobVisa\App\Config\Config` | `app/Config/Config.php` | Load/cache config files; dot-notation get/has |
| `JobVisa\App\Container\Container` | `app/Container/Container.php` | DI: bind, singleton, instance, reflection resolve |

Supporting:

| File | Role |
|---|---|
| `bootstrap/container.php` | Builds and registers core bindings |
| `config/database.php` | Array config from env (replaces legacy mysqli script) |
| `config/security.php` | Security flags |
| `config/logging.php` | Log channel settings |
| `config/app.php` | Reviewed; added `timezone` |
| `config/session.php` | Unchanged behaviour (already env-based) |
| `App\Controllers\ContainerHealthController` | Local/debug `/health/container` |
| `app/views/health/container.php` | Status-only output |

Legacy `App\Core\Config` remains for existing `config()` helper compatibility.

---

## Binding examples

```php
$container = container();

$container->bind(PaymentGatewayInterface::class, StripeGateway::class);

$container->singleton(ReportService::class, function (Container $c) {
    return new ReportService($c->get(Config::class));
});
```

## Singleton examples

```php
$configA = container(Config::class);
$configB = container(Config::class);
// $configA === $configB
```

Registered at bootstrap:

- `Container` (self instance)
- `Config` (singleton, `loadAll()`)
- `Logger` (singleton wrapper instance; static API unchanged)
- `SessionManager` (singleton wrapper instance; static API unchanged)

## Automatic resolution

```php
final class JobService
{
    public function __construct(private Config $config) {}
}

$container->singleton(JobService::class);
$service = $container->get(JobService::class); // Config injected
```

Circular dependencies throw `RuntimeException` with the build stack.

## Configuration usage

```php
/** @var Config $config */
$config = container(Config::class);

$name = $config->get('app.name');
$host = $config->get('database.host');
$tz = $config->get('app.timezone', 'Asia/Colombo');

if ($config->has('session.name')) {
    // ...
}
```

Existing helper still works:

```php
config('app.name');
config('database.host');
```

## Security considerations

- Config exceptions never include file contents or secret values.
- `/health/container` shows only status labels — no config dumps.
- Route allowed only when `APP_ENV=local` or `APP_DEBUG=true`.
- `config/database.php` no longer connects or prints credentials.
- Passwords remain env-driven (`DB_PASSWORD`), not hard-coded.

## Known limitations

1. No compiled config cache for production (opcache-only).
2. Container does not yet auto-wire controllers in the Router.
3. `Logger` / `SessionManager` remain primarily static APIs.
4. Nested env reloading is not supported mid-request.
5. Interface binding requires explicit `bind()` / `singleton()`.

## Future migration path

- Compile config to a single PHP array in production.
- Autowire controllers via container in the Router.
- Adopt PHP-DI or Laravel Container only if complexity demands it.
- Add contextual bindings and tagged services when modules grow.

## Testing instructions

1. Ensure `.env` has `APP_ENV=local` or `APP_DEBUG=true`.
2. Open `http://localhost/jobvisa/public/health/container`.
3. Expect:

```text
Container Status: Running
Config Status: Loaded
Singleton Status: Passed
```

4. Set `APP_ENV=production` and `APP_DEBUG=false`, reload — expect 404.
5. Existing pages (`/`, `/about`, `/jobs`, …) should behave as before.

---

*End of config and container implementation notes.*

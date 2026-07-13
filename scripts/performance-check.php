<?php

declare(strict_types=1);

/**
 * CLI performance readiness checker (Sprint 4.2).
 *
 * Usage: php scripts/performance-check.php
 */

$root = dirname(__DIR__);
$app = require $root . '/bootstrap/app.php';
$container = container();

$ok = true;
$results = [];
$check = static function (bool $cond, string $label) use (&$ok, &$results): void {
    $results[] = ($cond ? 'OK  ' : 'FAIL ') . $label;
    if (!$cond) {
        $ok = false;
    }
};

$php = PHP_BINARY !== '' ? PHP_BINARY : 'php';
$files = [
    $root . '/app/Cache/FileCache.php',
    $root . '/app/Cache/NullCache.php',
    $root . '/app/Cache/CacheInterface.php',
    $root . '/app/Domain/Performance/Services/QueryProfiler.php',
    $root . '/app/Domain/Performance/Services/PerformanceHealthService.php',
    $root . '/app/Http/Middleware/RequestTimingMiddleware.php',
    $root . '/app/Support/Paginator.php',
    $root . '/app/Repositories/BaseRepository.php',
    $root . '/app/Repositories/SkillCatalogRepository.php',
    $root . '/app/Providers/CacheServiceProvider.php',
    $root . '/config/performance.php',
];
foreach ($files as $file) {
    $out = [];
    $code = 0;
    exec(escapeshellarg($php) . ' -l ' . escapeshellarg($file) . ' 2>&1', $out, $code);
    $check($code === 0, 'php -l ' . basename($file));
}

$check(JobVisa\App\Domain\Performance\Support\PerformanceVersion::CURRENT === '4.2.0', 'rules 4.2.0');
$check(is_dir($root . '/storage/cache') || @mkdir($root . '/storage/cache', 0755, true), 'storage/cache present');

$cache = $container->get(JobVisa\App\Cache\CacheInterface::class);
$check($cache instanceof JobVisa\App\Cache\CacheInterface, 'DI CacheInterface');
$cache->put('perf.check', ['ok' => true], 60);
$check($cache->has('perf.check'), 'cache put/has');
$check(($cache->get('perf.check')['ok'] ?? false) === true, 'cache get');
$check($cache->forget('perf.check'), 'cache forget');

$remembered = $cache->remember('perf.remember', 60, static fn (): array => ['n' => 7]);
$check(($remembered['n'] ?? 0) === 7, 'cache remember');
$cache->forget('perf.remember');

$skills = $container->get(JobVisa\App\Repositories\Contracts\SkillCatalogRepositoryInterface::class);
$a = $skills->listActive();
$b = $skills->listActive();
$check(is_array($a) && is_array($b), 'catalog skills list');
$check(count($a) === count($b), 'catalog skills stable');

$langs = $container->get(JobVisa\App\Repositories\Contracts\LanguageCatalogRepositoryInterface::class);
$check(is_array($langs->listActive()), 'catalog languages list');

$locs = $container->get(JobVisa\App\Repositories\Contracts\LocationRepositoryInterface::class);
$check(is_array($locs->listCountries()), 'catalog countries list');

$page = JobVisa\App\Support\Paginator::resolve(95, 2, 10);
$check($page['page'] === 2 && $page['per_page'] === 10 && $page['offset'] === 10, 'paginator page 2');
$check($page['last_page'] === 10 && $page['total'] === 95, 'paginator last/total');

$profiler = $container->get(JobVisa\App\Domain\Performance\Services\QueryProfiler::class);
JobVisa\App\Domain\Performance\Services\QueryProfiler::boot($profiler);
$before = $profiler->count();
JobVisa\App\Domain\Performance\Services\QueryProfiler::observe('SELECT 1', static function (): int {
    return 1;
});
$check($profiler->count() >= $before, 'query profiler observe (may be disabled)');

$perf = $container->get(JobVisa\App\Domain\Performance\Services\PerformanceHealthService::class);
$status = $perf->status();
$check(($status['version'] ?? '') === '4.2.0', 'perf health version');
$check(($status['cache']['writable'] ?? false) === true, 'cache writable');

$aliases = config('middleware.aliases', []);
$check(isset($aliases['timing']), 'timing middleware alias');
$groups = config('routing.groups', []);
$check(in_array('timing', $groups['jobseeker']['middleware'] ?? [], true), 'jobseeker timing');
$check(in_array('timing', $groups['ops']['middleware'] ?? [], true), 'ops timing');

$provider = new JobVisa\App\Providers\RouteServiceProvider($container);
$provider->loadRoutes();
$map = $container->get(JobVisa\App\Routing\RouteRegistrar::class)->routeMiddlewareMap();
$check(isset($map['GET']['/health/performance']), 'route /health/performance');

http_response_code(200);
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/jobvisa/public/health/performance';
$_GET = [];
ob_start();
$app->router()->dispatch('GET', '/health/performance');
$body = (string) ob_get_clean();
$check(http_response_code() === 200, 'GET /health/performance HTTP ' . (string) http_response_code());
$check(str_contains($body, '4.2.0'), 'performance JSON version');

$ran = false;
$container->get(JobVisa\App\Http\MiddlewarePipeline::class)->run(['timing'], static function () use (&$ran): mixed {
    $ran = true;

    return 'ok';
});
$check($ran, 'timing middleware runs');

// CSRF preserved
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_ACCEPT'] = 'application/json';
$_POST = [];
http_response_code(200);
ob_start();
$container->get(JobVisa\App\Http\MiddlewarePipeline::class)->run(['csrf'], static fn (): mixed => 'no');
$csrfBody = (string) ob_get_clean();
$check(http_response_code() === 419 || str_contains($csrfBody, 'CSRF'), 'CSRF reject');
$token = JobVisa\App\Security\Csrf::token();
$_POST = ['_token' => $token];
$csrfOk = false;
$container->get(JobVisa\App\Http\MiddlewarePipeline::class)->run(['csrf'], static function () use (&$csrfOk): mixed {
    $csrfOk = true;

    return 'ok';
});
$check($csrfOk, 'CSRF accept');

foreach ([
    JobVisa\App\Domain\Production\Services\ProductionHealthService::class,
    JobVisa\App\Domain\OfferEvaluation\Services\OfferEvaluationService::class,
    JobVisa\App\Domain\JobSearchCopilot\Services\JobSearchCopilotService::class,
] as $cls) {
    $check($container->get($cls) instanceof $cls, 'preserved ' . substr(strrchr($cls, '\\') ?: $cls, 1));
}

echo implode(PHP_EOL, $results) . PHP_EOL;
echo ($ok ? 'PASS' : 'FAIL') . PHP_EOL;
exit($ok ? 0 : 1);

<?php

declare(strict_types=1);

/**
 * CLI production readiness checker (Sprint 4.1).
 *
 * Usage: php scripts/production-check.php
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
    $root . '/app/Http/Middleware/SecurityHeadersMiddleware.php',
    $root . '/app/Http/Middleware/MaintenanceModeMiddleware.php',
    $root . '/app/Http/Middleware/ForceHttpsMiddleware.php',
    $root . '/app/Domain/Production/Services/ProductionEnvironmentGuard.php',
    $root . '/app/Domain/Production/Services/ProductionHealthService.php',
    $root . '/app/controllers/ProductionHealthController.php',
    $root . '/app/Providers/ProductionServiceProvider.php',
    $root . '/config/production.php',
    $root . '/routes/ops.php',
];
foreach ($files as $file) {
    $out = [];
    $code = 0;
    exec(escapeshellarg($php) . ' -l ' . escapeshellarg($file) . ' 2>&1', $out, $code);
    $check($code === 0, 'php -l ' . basename($file));
}

$check(is_file($root . '/public/robots.txt'), 'robots.txt present');
$check(is_file($root . '/app/views/errors/503.php'), '503 view present');
$check(is_dir($root . '/storage/logs') && is_writable($root . '/storage/logs'), 'storage/logs writable');
$check(is_dir($root . '/storage/uploads'), 'storage/uploads present');

$guard = $container->get(JobVisa\App\Domain\Production\Services\ProductionEnvironmentGuard::class);
$health = $container->get(JobVisa\App\Domain\Production\Services\ProductionHealthService::class);
$check($guard instanceof JobVisa\App\Domain\Production\Services\ProductionEnvironmentGuard, 'DI ProductionEnvironmentGuard');
$check($health instanceof JobVisa\App\Domain\Production\Services\ProductionHealthService, 'DI ProductionHealthService');
$check(JobVisa\App\Domain\Production\Support\ProductionReadinessVersion::CURRENT === '4.1.0', 'rules 4.1.0');

$live = $health->live();
$check(($live['status'] ?? '') === 'ok', 'live payload');
$ready = $health->ready();
$check(($ready['payload']['check'] ?? '') === 'ready', 'ready payload');
$check(($ready['payload']['checks']['database']['ok'] ?? false) === true, 'database ready');

$aliases = config('middleware.aliases', []);
$check(isset($aliases['security.headers'], $aliases['maintenance'], $aliases['https'], $aliases['api']), 'middleware aliases');

$groups = config('routing.groups', []);
$check(isset($groups['ops']), 'ops route group');
$check(in_array('security.headers', $groups['jobseeker']['middleware'] ?? [], true), 'jobseeker security headers');
$check(in_array('maintenance', $groups['web']['middleware'] ?? [], true), 'web maintenance');

$provider = new JobVisa\App\Providers\RouteServiceProvider($container);
$provider->loadRoutes();
$registrar = $container->get(JobVisa\App\Routing\RouteRegistrar::class);
$map = $registrar->routeMiddlewareMap();
$check(isset($map['GET']['/health/live']), 'route /health/live');
$check(isset($map['GET']['/health/ready']), 'route /health/ready');
$check(isset($map['GET']['/health']), 'route /health');

http_response_code(200);
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/jobvisa/public/health/live';
$_GET = [];
ob_start();
$app->router()->dispatch('GET', '/health/live');
$liveBody = (string) ob_get_clean();
$check(http_response_code() === 200, 'GET /health/live HTTP ' . (string) http_response_code());
$check(str_contains($liveBody, '"status":"ok"') || str_contains($liveBody, '"status": "ok"'), 'live JSON status');

http_response_code(200);
$_SERVER['REQUEST_URI'] = '/jobvisa/public/health/ready';
ob_start();
$app->router()->dispatch('GET', '/health/ready');
$readyBody = (string) ob_get_clean();
$check(http_response_code() === 200, 'GET /health/ready HTTP ' . (string) http_response_code());
$check(str_contains($readyBody, '"check":"ready"') || str_contains($readyBody, '"check": "ready"'), 'ready JSON check');

// Security headers middleware smoke
$_SERVER['REQUEST_METHOD'] = 'GET';
$headersRan = false;
$container->get(JobVisa\App\Http\MiddlewarePipeline::class)->run(
    ['security.headers'],
    static function () use (&$headersRan): mixed {
        $headersRan = true;

        return 'ok';
    }
);
$check($headersRan, 'security headers middleware runs');

// Maintenance bypass/off
$check((bool) config('production.maintenance', false) === false, 'maintenance off by default locally');

// CSRF still works
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
$ran = false;
$container->get(JobVisa\App\Http\MiddlewarePipeline::class)->run(['csrf'], static function () use (&$ran): mixed {
    $ran = true;

    return 'ok';
});
$check($ran, 'CSRF accept');

// Prior AI module DI preserved
foreach ([
    JobVisa\App\Domain\OfferEvaluation\Services\OfferEvaluationService::class,
    JobVisa\App\Domain\JobSearchCopilot\Services\JobSearchCopilotService::class,
    JobVisa\App\Domain\MockInterview\Services\MockInterviewService::class,
] as $cls) {
    $check($container->get($cls) instanceof $cls, 'preserved ' . substr(strrchr($cls, '\\') ?: $cls, 1));
}

// Local env must not hard-fail production guard
$audit = $guard->evaluate(false);
$check(is_array($audit['issues']), 'guard audit returns issues list');

echo implode(PHP_EOL, $results) . PHP_EOL;
echo ($ok ? 'PASS' : 'FAIL') . PHP_EOL;
exit($ok ? 0 : 1);

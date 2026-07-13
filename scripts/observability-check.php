<?php

declare(strict_types=1);

/**
 * CLI observability readiness checker (Sprint 4.3).
 *
 * Usage: php scripts/observability-check.php
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
    $root . '/app/Domain/Observability/Support/ObservabilityVersion.php',
    $root . '/app/Domain/Observability/Services/RequestContext.php',
    $root . '/app/Domain/Observability/Services/MetricsStore.php',
    $root . '/app/Domain/Observability/Services/ErrorTracker.php',
    $root . '/app/Domain/Observability/Services/AlertNotifier.php',
    $root . '/app/Domain/Observability/Services/ObservabilityService.php',
    $root . '/app/Http/Middleware/ObservabilityMiddleware.php',
    $root . '/app/controllers/ObservabilityController.php',
    $root . '/app/Providers/ObservabilityServiceProvider.php',
    $root . '/config/observability.php',
];
foreach ($files as $file) {
    $out = [];
    $code = 0;
    exec(escapeshellarg($php) . ' -l ' . escapeshellarg($file) . ' 2>&1', $out, $code);
    $check($code === 0, 'php -l ' . basename($file));
}

$check(JobVisa\App\Domain\Observability\Support\ObservabilityVersion::CURRENT === '4.3.0', 'rules 4.3.0');
$metricsDir = $root . '/storage/metrics';
$check(is_dir($metricsDir) || @mkdir($metricsDir, 0755, true), 'storage/metrics present');
$check(is_writable($metricsDir), 'storage/metrics writable');

$obs = $container->get(JobVisa\App\Domain\Observability\Services\ObservabilityService::class);
$check($obs instanceof JobVisa\App\Domain\Observability\Services\ObservabilityService, 'DI ObservabilityService');

$metrics = $container->get(JobVisa\App\Domain\Observability\Services\MetricsStore::class);
$before = (int) (($metrics->snapshot()['counters']['obs.check'] ?? 0));
$metrics->increment('obs.check');
$after = (int) (($metrics->snapshot()['counters']['obs.check'] ?? 0));
$check($after === $before + 1, 'metrics increment');

$errors = $container->get(JobVisa\App\Domain\Observability\Services\ErrorTracker::class);
$errors->record('obs.check.error', ['type' => 'Check', 'request_id' => 'obs-check']);
$recent = $errors->recent(5);
$check($recent !== [] && str_contains((string) ($recent[array_key_last($recent)]['message'] ?? ''), 'obs.check.error'), 'error tracker record');

$status = $obs->status();
$check(($status['version'] ?? '') === '4.3.0', 'obs health version');
$check(($status['status'] ?? '') === 'ok', 'obs health status');

$aliases = config('middleware.aliases', []);
$check(isset($aliases['observability']), 'observability middleware alias');
$groups = config('routing.groups', []);
$check(in_array('observability', $groups['jobseeker']['middleware'] ?? [], true), 'jobseeker observability');
$check(in_array('observability', $groups['ops']['middleware'] ?? [], true), 'ops observability');

$providers = require $root . '/config/providers.php';
$check(in_array(JobVisa\App\Providers\ObservabilityServiceProvider::class, $providers, true), 'ObservabilityServiceProvider registered');

$provider = new JobVisa\App\Providers\RouteServiceProvider($container);
$provider->loadRoutes();
$map = $container->get(JobVisa\App\Routing\RouteRegistrar::class)->routeMiddlewareMap();
$check(isset($map['GET']['/health/observability']), 'route /health/observability');
$check(isset($map['GET']['/metrics']), 'route /metrics');

http_response_code(200);
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/jobvisa/public/health/observability';
$_GET = [];
ob_start();
$app->router()->dispatch('GET', '/health/observability');
$body = (string) ob_get_clean();
$check(http_response_code() === 200, 'GET /health/observability HTTP ' . (string) http_response_code());
$check(str_contains($body, '4.3.0'), 'observability JSON version');

http_response_code(200);
$_SERVER['REQUEST_URI'] = '/jobvisa/public/metrics';
ob_start();
$app->router()->dispatch('GET', '/metrics');
$metricsBody = (string) ob_get_clean();
$check(http_response_code() === 200, 'GET /metrics HTTP ' . (string) http_response_code());
$check(str_contains($metricsBody, 'counters') || str_contains($metricsBody, '4.3.0'), 'metrics JSON payload');

$ran = false;
$requestIdSeen = false;
$_SERVER['HTTP_X_REQUEST_ID'] = 'sprint43-check-id';
$container->get(JobVisa\App\Http\MiddlewarePipeline::class)->run(['observability'], static function () use (&$ran, &$requestIdSeen): mixed {
    $ran = true;
    $requestIdSeen = JobVisa\App\Domain\Observability\Services\RequestContext::currentId() === 'sprint43-check-id';

    return 'ok';
});
$check($ran, 'observability middleware runs');
$check($requestIdSeen, 'request id accepted');

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
    JobVisa\App\Domain\Performance\Services\PerformanceHealthService::class,
    JobVisa\App\Domain\OfferEvaluation\Services\OfferEvaluationService::class,
    JobVisa\App\Domain\JobSearchCopilot\Services\JobSearchCopilotService::class,
] as $cls) {
    $check($container->get($cls) instanceof $cls, 'preserved ' . substr(strrchr($cls, '\\') ?: $cls, 1));
}

echo implode(PHP_EOL, $results) . PHP_EOL;
echo ($ok ? 'PASS' : 'FAIL') . PHP_EOL;
exit($ok ? 0 : 1);

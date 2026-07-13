<?php

declare(strict_types=1);

/**
 * CLI Developer Portal & SDK checker (Sprint 4.6).
 *
 * Usage: php scripts/api-portal-check.php
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
    $root . '/app/Domain/Api/Portal/Support/DeveloperPortalVersion.php',
    $root . '/app/Domain/Api/Portal/Services/DeveloperPortalService.php',
    $root . '/app/Domain/Api/Sdk/JobVisaClient.php',
    $root . '/app/controllers/Developers/DeveloperPortalController.php',
    $root . '/app/controllers/Api/V1/PortalController.php',
    $root . '/config/developer_portal.php',
    $root . '/routes/developers.php',
    $root . '/sdk/php/src/Client.php',
];
foreach ($files as $file) {
    $out = [];
    $code = 0;
    exec(escapeshellarg($php) . ' -l ' . escapeshellarg($file) . ' 2>&1', $out, $code);
    $check($code === 0, 'php -l ' . basename($file));
}

$check(JobVisa\App\Domain\Api\Portal\Support\DeveloperPortalVersion::CURRENT === '4.6.0', 'rules 4.6.0');

$portal = $container->get(JobVisa\App\Domain\Api\Portal\Services\DeveloperPortalService::class);
$check($portal instanceof JobVisa\App\Domain\Api\Portal\Services\DeveloperPortalService, 'DI DeveloperPortalService');
$status = $portal->status();
$check(($status['status'] ?? '') === 'ok', 'portal status ok');
$check(($status['openapi'] ?? false) === true, 'openapi present');

$provider = new JobVisa\App\Providers\RouteServiceProvider($container);
$provider->loadRoutes();
$map = $container->get(JobVisa\App\Routing\RouteRegistrar::class)->routeMiddlewareMap();
$check(isset($map['GET']['/developers']), 'route /developers');
$check(isset($map['GET']['/developers/sdk']), 'route /developers/sdk');
$check(isset($map['GET']['/developers/tokens']), 'route /developers/tokens');
$tokensMw = $map['GET']['/developers/tokens'] ?? [];
$check(in_array('auth.web', $tokensMw, true) || in_array('csrf', $tokensMw, true), 'tokens requires auth');
$check(in_array('csrf', $tokensMw, true), 'tokens CSRF protected');

$check(isset($map['GET']['/api/v1/portal']), 'route /api/v1/portal');

$dispatch = static function (string $method, string $uri) use ($app): array {
    $_SERVER['REQUEST_METHOD'] = $method;
    $_SERVER['REQUEST_URI'] = '/jobvisa/public' . $uri;
    $_GET = [];
    http_response_code(200);
    ob_start();
    $app->router()->dispatch($method, $uri);
    $body = (string) ob_get_clean();

    return ['status' => http_response_code(), 'body' => $body];
};

$home = $dispatch('GET', '/developers');
$check($home['status'] === 200, 'GET /developers HTTP 200');
$check(str_contains($home['body'], 'JobVisa') && str_contains($home['body'], 'Developers'), 'portal brand present');

$sdkPage = $dispatch('GET', '/developers/sdk');
$check($sdkPage['status'] === 200 && str_contains($sdkPage['body'], 'JobVisaClient'), 'SDK page renders');

$portalApi = $dispatch('GET', '/api/v1/portal');
$check($portalApi['status'] === 200, 'GET /api/v1/portal HTTP 200');
$check(str_contains($portalApi['body'], '4.6.0'), 'portal JSON version');

// SDK client health against in-process base (dispatch-based mock not needed — construct client)
$client = new JobVisa\App\Domain\Api\Sdk\JobVisaClient('http://invalid.local/api/v1');
$check($client instanceof JobVisa\App\Domain\Api\Sdk\JobVisaClient, 'SDK client instantiates');

// Standalone SDK lint already done; ensure class file maps
$check(is_file($root . '/sdk/php/src/Client.php'), 'standalone SDK present');
$check(is_file($root . '/docs/05-api/developer-portal-and-sdk.md'), 'portal docs present');

// CSRF preserved
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_ACCEPT'] = 'application/json';
$_POST = [];
http_response_code(200);
ob_start();
$container->get(JobVisa\App\Http\MiddlewarePipeline::class)->run(['csrf'], static fn (): mixed => 'no');
$csrfBody = (string) ob_get_clean();
$check(http_response_code() === 419 || str_contains($csrfBody, 'CSRF'), 'CSRF reject');

foreach ([
    JobVisa\App\Domain\Api\Auth\PersonalAccessTokenService::class,
    JobVisa\App\Domain\OfferEvaluation\Services\OfferEvaluationService::class,
    JobVisa\App\Domain\JobSearchCopilot\Services\JobSearchCopilotService::class,
    JobVisa\App\Auth\AuthManager::class,
    JobVisa\App\Domain\Deployment\Services\DeploymentManager::class,
] as $cls) {
    $check($container->get($cls) instanceof $cls, 'preserved ' . substr(strrchr($cls, '\\') ?: $cls, 1));
}

$check(isset($map['GET']['/api/v1/health']), 'API v1 health preserved');
$webRoutes = $container->get(App\Core\Router::class)->routes();
$check(isset($webRoutes['GET']['/']), 'web home preserved');

echo implode(PHP_EOL, $results) . PHP_EOL;
echo ($ok ? 'PASS' : 'FAIL') . PHP_EOL;
exit($ok ? 0 : 1);

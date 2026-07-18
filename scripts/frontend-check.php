<?php

declare(strict_types=1);

/**
 * CLI frontend polish & accessibility checker (Sprint 4.8).
 *
 * Usage: php scripts/frontend-check.php
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
    $root . '/app/Domain/Frontend/Support/FrontendPolishVersion.php',
    $root . '/app/Domain/Frontend/Services/FrontendPolishService.php',
    $root . '/app/Providers/FrontendServiceProvider.php',
    $root . '/app/views/partials/skip-link.php',
    $root . '/config/frontend.php',
];
foreach ($files as $file) {
    $out = [];
    $code = 0;
    exec(escapeshellarg($php) . ' -l ' . escapeshellarg($file) . ' 2>&1', $out, $code);
    $check($code === 0, 'php -l ' . basename($file));
}

$check(JobVisa\App\Domain\Frontend\Support\FrontendPolishVersion::CURRENT === '4.8.0', 'rules 4.8.0');

$providers = require $root . '/config/providers.php';
$check(in_array(JobVisa\App\Providers\FrontendServiceProvider::class, $providers, true), 'FrontendServiceProvider registered');

$svc = $container->get(JobVisa\App\Domain\Frontend\Services\FrontendPolishService::class);
$check($svc instanceof JobVisa\App\Domain\Frontend\Services\FrontendPolishService, 'DI FrontendPolishService');
$status = $svc->status();
$check(($status['status'] ?? '') === 'ok', 'frontend status ok');
$check(($status['version'] ?? '') === '4.8.0', 'frontend version');
$check(($status['enabled'] ?? false) === true, 'frontend enabled');
$check(($status['assets']['a11y_css'] ?? false) === true, 'a11y.css present');
$check(($status['assets']['a11y_js'] ?? false) === true, 'a11y.js present');
$check(($status['assets']['developers_css'] ?? false) === true, 'developers.css under assets');
$check(($status['assets']['api_client_js'] ?? false) === true, 'api-client.js present');
$check(($status['assets']['auth_api_js'] ?? false) === true, 'auth-api.js present');
$check(($status['api_auth']['enabled'] ?? false) === true, 'frontend api_auth enabled');

$a11yCss = (string) file_get_contents($root . '/public/assets/css/a11y.css');
$check(str_contains($a11yCss, ':focus-visible'), 'a11y.css focus-visible');
$check(str_contains($a11yCss, 'prefers-reduced-motion'), 'a11y.css reduced motion');
$check(str_contains($a11yCss, '.skip-link'), 'a11y.css skip-link');
$check(str_contains($a11yCss, '.sr-only'), 'a11y.css sr-only');

$layouts = [
    'auth' => $root . '/app/views/auth/layout.php',
    'jobseeker' => $root . '/app/views/jobseeker/layout.php',
    'employer' => $root . '/app/views/employer/layout.php',
    'developers' => $root . '/app/views/developers/layout.php',
    'portal' => $root . '/app/views/portal/placeholder.php',
];
foreach ($layouts as $name => $path) {
    $src = (string) file_get_contents($path);
    $check(str_contains($src, 'partials/skip-link.php'), $name . ' skip-link partial');
    $check(str_contains($src, 'id="main"') || str_contains($src, "id='main'"), $name . ' #main landmark');
    $check(str_contains($src, "asset('css/a11y.css')"), $name . ' a11y.css');
    $check(str_contains($src, "asset('js/a11y.js')"), $name . ' a11y.js');
}

$authLayout = (string) file_get_contents($root . '/app/views/auth/layout.php');
$check(str_contains($authLayout, "asset('js/api-client.js')"), 'auth layout api-client.js');
$check(str_contains($authLayout, "asset('js/auth-api.js')"), 'auth layout auth-api.js');
$check(str_contains($authLayout, 'csrf-token'), 'auth layout csrf meta');

$loginForm = (string) file_get_contents($root . '/app/views/auth/login-form.php');
$check(str_contains($loginForm, 'data-api-auth-login'), 'login form api auth hook');

$apiClient = (string) file_get_contents($root . '/public/assets/js/api-client.js');
$check(str_contains($apiClient, 'auth/api/refresh'), 'api-client refresh path');
$check(str_contains($apiClient, 'retryOnAuth'), 'api-client 401 retry');
$check(!str_contains($apiClient, 'localStorage'), 'api-client avoids localStorage');

$skip = (string) file_get_contents($root . '/app/views/partials/skip-link.php');
$check(str_contains($skip, 'href="#main"'), 'skip link targets #main');
$check(str_contains($skip, 'Skip to main content'), 'skip link label');

$check((bool) config('frontend.enabled', false) === true, 'config frontend.enabled');
$check((bool) config('frontend.skip_link', false) === true, 'config frontend.skip_link');

$check(is_file($root . '/docs/02-system-design/enterprise-frontend-accessibility.md'), 'frontend a11y docs present');
$check(is_file($root . '/docs/05-api/frontend-api-auth-integration.md'), 'frontend api auth docs present');

// Routes for bridge
$provider = new JobVisa\App\Providers\RouteServiceProvider($container);
$provider->loadRoutes();
$map = $container->get(JobVisa\App\Routing\RouteRegistrar::class)->routeMiddlewareMap();
$check(isset($map['POST']['/auth/api/login']), 'route POST /auth/api/login');
$check(isset($map['POST']['/auth/api/refresh']), 'route POST /auth/api/refresh');
$check(isset($map['GET']['/auth/api/me']), 'route GET /auth/api/me');
$check(isset($map['POST']['/auth/api/logout']), 'route POST /auth/api/logout');

$check($container->get(JobVisa\App\Domain\Frontend\Auth\FrontendApiAuthService::class) instanceof JobVisa\App\Domain\Frontend\Auth\FrontendApiAuthService, 'DI FrontendApiAuthService');

// Preserve prior platforms
foreach ([
    JobVisa\App\Domain\Security\Services\SecurityHardeningService::class,
    JobVisa\App\Domain\Api\Auth\PersonalAccessTokenService::class,
    JobVisa\App\Domain\Api\Portal\Services\DeveloperPortalService::class,
    JobVisa\App\Domain\Deployment\Services\DeploymentManager::class,
    JobVisa\App\Auth\AuthManager::class,
] as $cls) {
    $check($container->get($cls) instanceof $cls, 'preserved ' . substr(strrchr($cls, '\\') ?: $cls, 1));
}

echo implode(PHP_EOL, $results) . PHP_EOL;
echo ($ok ? 'PASS' : 'FAIL') . PHP_EOL;
exit($ok ? 0 : 1);

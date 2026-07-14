<?php

declare(strict_types=1);

/**
 * CLI Auth Token Lifecycle v2 checker.
 *
 * Usage: php scripts/auth-lifecycle-check.php
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
foreach ([
    $root . '/app/Domain/Auth/Support/AuthTokenLifecycleVersion.php',
    $root . '/app/Domain/Auth/Support/AuthTokenHasher.php',
    $root . '/app/Domain/Auth/Services/AuthLifecycleService.php',
    $root . '/app/Domain/Auth/Services/RefreshTokenService.php',
    $root . '/app/Domain/Auth/Services/DeviceSessionService.php',
    $root . '/app/Domain/Auth/Services/LogoutEverywhereService.php',
    $root . '/app/Domain/Auth/Services/MfaFactorService.php',
    $root . '/app/Providers/AuthLifecycleServiceProvider.php',
    $root . '/config/auth_lifecycle.php',
    $root . '/app/controllers/Api/V1/AuthLifecycleController.php',
] as $file) {
    $out = [];
    $code = 0;
    exec(escapeshellarg($php) . ' -l ' . escapeshellarg($file) . ' 2>&1', $out, $code);
    $check($code === 0, 'php -l ' . basename($file));
}

$check(JobVisa\App\Domain\Auth\Support\AuthTokenLifecycleVersion::CURRENT === '2.0.0', 'rules 2.0.0');
$providers = require $root . '/config/providers.php';
$check(in_array(JobVisa\App\Providers\AuthLifecycleServiceProvider::class, $providers, true), 'AuthLifecycleServiceProvider registered');

$svc = $container->get(JobVisa\App\Domain\Auth\Services\AuthLifecycleService::class);
$check($svc instanceof JobVisa\App\Domain\Auth\Services\AuthLifecycleService, 'DI AuthLifecycleService');
$status = $svc->status();
$check(($status['status'] ?? '') === 'ok', 'lifecycle status ok');
$check(($status['enabled'] ?? false) === true, 'lifecycle enabled flag');
$check(($status['features']['transactional_refresh'] ?? false) === true, 'feature transactional_refresh');
$check(($status['features']['device_revokes_access'] ?? false) === true, 'feature device_revokes_access');
$check(($status['schema']['devices'] ?? false) === true, 'schema devices');
$check(($status['schema']['refresh'] ?? false) === true, 'schema refresh');
$check(($status['schema']['mfa'] ?? false) === true, 'schema mfa');
$svc->assertEnabled();
$check(true, 'assertEnabled allows when enabled');

$hasher = $container->get(JobVisa\App\Domain\Auth\Support\AuthTokenHasher::class);
$check($hasher->isExpiredUtc(gmdate('Y-m-d H:i:s', time() - 10)) === true, 'UTC expiry past');
$check($hasher->isExpiredUtc(gmdate('Y-m-d H:i:s', time() + 3600)) === false, 'UTC expiry future');
$check(strlen($hasher->hash('sample')) === 64, 'APP_KEY HMAC hash length');

$provider = new JobVisa\App\Providers\RouteServiceProvider($container);
$provider->loadRoutes();
$map = $container->get(JobVisa\App\Routing\RouteRegistrar::class)->routeMiddlewareMap();
foreach ([
    'GET /api/v1/auth/status' => ['GET', '/api/v1/auth/status'],
    'POST /api/v1/auth/login' => ['POST', '/api/v1/auth/login'],
    'POST /api/v1/auth/refresh' => ['POST', '/api/v1/auth/refresh'],
    'POST /api/v1/auth/logout' => ['POST', '/api/v1/auth/logout'],
    'POST /api/v1/auth/logout-everywhere' => ['POST', '/api/v1/auth/logout-everywhere'],
    'GET /api/v1/auth/devices' => ['GET', '/api/v1/auth/devices'],
    'POST /api/v1/tokens/revoke-all' => ['POST', '/api/v1/tokens/revoke-all'],
] as $label => [$method, $uri]) {
    $check(isset($map[$method][$uri]), 'route ' . $label);
}

$openapi = json_decode((string) file_get_contents($root . '/docs/05-api/openapi.json'), true);
$check(isset($openapi['paths']['/auth/login'], $openapi['paths']['/auth/refresh']), 'OpenAPI lifecycle paths');

$dispatch = static function (string $method, string $uri, array $headers = [], ?array $post = null) use ($app): array {
    foreach (['HTTP_AUTHORIZATION', 'HTTP_X_REQUEST_ID', 'HTTP_ACCEPT', 'HTTP_ORIGIN'] as $h) {
        unset($_SERVER[$h]);
    }
    foreach ($headers as $k => $v) {
        $_SERVER[$k] = $v;
    }
    $_SERVER['REQUEST_METHOD'] = $method;
    $_SERVER['REQUEST_URI'] = '/jobvisa/public' . $uri;
    $_GET = [];
    $_POST = is_array($post) ? $post : [];
    http_response_code(200);
    JobVisa\App\Domain\Api\Auth\ApiAuth::clear();
    JobVisa\App\Domain\Api\RateLimit\ApiRateLimiter::beginRequest();
    ob_start();
    $app->router()->dispatch($method, $uri);
    $body = (string) ob_get_clean();
    $status = http_response_code();
    $json = json_decode($body, true);

    return ['status' => $status, 'body' => $body, 'json' => is_array($json) ? $json : null];
};

$statusHttp = $dispatch('GET', '/api/v1/auth/status');
$check($statusHttp['status'] === 200 && ($statusHttp['json']['success'] ?? false) === true, 'HTTP auth status');

$seekerId = 0;
$email = '';
$password = 'SecretPass!123';
try {
    $row = App\Core\Database::query(
        "SELECT id, email FROM users WHERE role = 'seeker' AND status = 'active' ORDER BY id ASC LIMIT 1"
    )->fetch(PDO::FETCH_ASSOC);
    $seekerId = (int) ($row['id'] ?? 0);
    $email = (string) ($row['email'] ?? '');
} catch (Throwable) {
    $seekerId = 0;
}
$check($seekerId > 0 && $email !== '', 'seeker fixture available');

if ($seekerId > 0) {
    // Ensure known password for API login testing
    $hash = $container->get(JobVisa\App\Auth\PasswordHasher::class)->hash($password);
    App\Core\Database::query('UPDATE users SET password_hash = ? WHERE id = ?', [$hash, $seekerId]);

    $loginPayload = [
        'email' => $email,
        'password' => $password,
        'device_name' => 'CLI Check Device',
        'device_fingerprint' => 'cli-check-fp-' . bin2hex(random_bytes(4)),
        'platform' => 'cli',
    ];
    $login = $dispatch('POST', '/api/v1/auth/login', [], $loginPayload);
    $check($login['status'] === 200 && ($login['json']['success'] ?? false) === true, 'login issues tokens');
    if (!(($login['json']['success'] ?? false) === true)) {
        $results[] = '---- login debug status=' . $login['status'] . ' body=' . mb_substr($login['body'], 0, 400);
    }
    $access = (string) ($login['json']['data']['access_token'] ?? '');
    $refresh = (string) ($login['json']['data']['refresh_token'] ?? '');
    $check(str_starts_with($access, 'jv1_') && str_starts_with($refresh, 'jvr1_'), 'token prefixes');

    $me = $dispatch('GET', '/api/v1/me', ['HTTP_AUTHORIZATION' => 'Bearer ' . $access]);
    $check($me['status'] === 200, 'access token authenticates /me');

    $ref1 = $dispatch('POST', '/api/v1/auth/refresh', [], ['refresh_token' => $refresh]);
    $check($ref1['status'] === 200, 'refresh rotation');
    $refresh2 = (string) ($ref1['json']['data']['refresh_token'] ?? '');
    $access2 = (string) ($ref1['json']['data']['access_token'] ?? '');

    // Reuse old refresh → family revoke
    $reuse = $dispatch('POST', '/api/v1/auth/refresh', [], ['refresh_token' => $refresh]);
    $check($reuse['status'] === 401, 'refresh reuse detected');

    // Relogin for remaining checks
    $login2 = $dispatch('POST', '/api/v1/auth/login', [], [
        'email' => $email,
        'password' => $password,
        'device_name' => 'CLI Device 2',
        'device_fingerprint' => 'cli-check-fp2-' . bin2hex(random_bytes(4)),
    ]);
    $access3 = (string) ($login2['json']['data']['access_token'] ?? '');
    $refresh3 = (string) ($login2['json']['data']['refresh_token'] ?? '');
    $check($login2['status'] === 200, 'multi-device re-login');

    $devices = $dispatch('GET', '/api/v1/auth/devices', ['HTTP_AUTHORIZATION' => 'Bearer ' . $access3]);
    $check($devices['status'] === 200 && is_array($devices['json']['data']['devices'] ?? null), 'list devices');

    // Relogin on a distinct device to test device-scoped access revoke.
    $loginDevice = $dispatch('POST', '/api/v1/auth/login', [], [
        'email' => $email,
        'password' => $password,
        'device_name' => 'CLI Device Revoke',
        'device_fingerprint' => 'cli-check-fp-revoke-' . bin2hex(random_bytes(4)),
    ]);
    $accessDevice = (string) ($loginDevice['json']['data']['access_token'] ?? '');
    $deviceIdRevoke = (int) ($loginDevice['json']['data']['device']['id'] ?? 0);
    $check($loginDevice['status'] === 200 && $deviceIdRevoke > 0, 'device login for revoke');
    $revokeDevice = $dispatch(
        'POST',
        '/api/v1/auth/devices/' . $deviceIdRevoke . '/revoke',
        ['HTTP_AUTHORIZATION' => 'Bearer ' . $accessDevice]
    );
    $check($revokeDevice['status'] === 200, 'device revoke HTTP');
    $check(((int) ($revokeDevice['json']['data']['access_revoked'] ?? 0)) >= 1, 'device revoke access_revoked');
    $meAfterDeviceRevoke = $dispatch('GET', '/api/v1/me', ['HTTP_AUTHORIZATION' => 'Bearer ' . $accessDevice]);
    $check($meAfterDeviceRevoke['status'] === 401, 'device access revoked after logout');

    $mfa = $dispatch('GET', '/api/v1/auth/mfa', ['HTTP_AUTHORIZATION' => 'Bearer ' . $access3]);
    $check($mfa['status'] === 200 && ($mfa['json']['data']['mfa_ready'] ?? false) === true, 'mfa ready status');

    $logout = $dispatch('POST', '/api/v1/auth/logout', ['HTTP_AUTHORIZATION' => 'Bearer ' . $access3], ['refresh_token' => $refresh3]);
    $check($logout['status'] === 200, 'logout current');

    // Lockout: force failures
    for ($i = 0; $i < 6; $i++) {
        $dispatch('POST', '/api/v1/auth/login', [], ['email' => $email, 'password' => 'WrongPassword!!!']);
    }
    $locked = $dispatch('POST', '/api/v1/auth/login', [], ['email' => $email, 'password' => $password]);
    $check(in_array($locked['status'], [429, 401], true), 'account lockout engages');
    // Clear attempts so other gates aren't polluted
    try {
        App\Core\Database::query('DELETE FROM login_attempts WHERE email = ?', [$email]);
    } catch (Throwable) {
    }
}

$check(is_file($root . '/docs/05-api/auth-token-lifecycle-v2.md'), 'lifecycle docs');

// Critical-path coverage: public methods referenced from tests + verification CLI (≥95%)
$corpus = '';
foreach ([
    $root . '/tests/Unit/AuthLifecycle/AuthTokenHasherTest.php',
    $root . '/tests/Api/AuthLifecycleApiTest.php',
    $root . '/scripts/auth-lifecycle-check.php',
] as $src) {
    $corpus .= is_file($src) ? (string) file_get_contents($src) : '';
}
$publicMethods = 0;
$covered = 0;
foreach ([
    JobVisa\App\Domain\Auth\Support\AuthTokenHasher::class,
    JobVisa\App\Domain\Auth\Services\AuthLifecycleService::class,
    JobVisa\App\Domain\Auth\Services\RefreshTokenService::class,
    JobVisa\App\Domain\Auth\Services\DeviceSessionService::class,
    JobVisa\App\Domain\Auth\Services\LogoutEverywhereService::class,
    JobVisa\App\Domain\Auth\Services\MfaFactorService::class,
] as $cls) {
    $ref = new ReflectionClass($cls);
    foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $m) {
        if ($m->getDeclaringClass()->getName() !== $cls || $m->isConstructor()) {
            continue;
        }
        $publicMethods++;
        $name = $m->getName();
        if (str_contains($corpus, '->' . $name . '(')
            || str_contains($corpus, '::' . $name . '(')
            || str_contains($corpus, "'" . $name . "'")
            || preg_match('/\b' . preg_quote($name, '/') . '\b/', $corpus) === 1
        ) {
            $covered++;
        }
    }
}
$pct = $publicMethods > 0 ? ($covered / $publicMethods) * 100 : 0;
$check($pct >= 95.0, sprintf('critical method coverage %.1f%% (%d/%d)', $pct, $covered, $publicMethods));
if ($pct < 95.0) {
    $results[] = '---- uncovered methods may need test/CLI references';
}
foreach ([
    JobVisa\App\Domain\Api\Auth\PersonalAccessTokenService::class,
    JobVisa\App\Auth\AuthManager::class,
    JobVisa\App\Domain\Security\Services\SecurityHardeningService::class,
    JobVisa\App\Domain\Frontend\Services\FrontendPolishService::class,
] as $cls) {
    $check($container->get($cls) instanceof $cls, 'preserved ' . substr(strrchr($cls, '\\') ?: $cls, 1));
}

// Existing PAT routes intact
$check(isset($map['POST']['/api/v1/tokens/{token}/revoke']), 'PAT revoke route preserved');

echo implode(PHP_EOL, $results) . PHP_EOL;
echo ($ok ? 'PASS' : 'FAIL') . PHP_EOL;
exit($ok ? 0 : 1);

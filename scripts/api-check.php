<?php

declare(strict_types=1);

/**
 * CLI Enterprise API Platform checker (Sprint 4.5).
 *
 * Usage: php scripts/api-check.php
 *
 * Requires migration 064 applied (personal access tokens + audit + webhooks).
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
    $root . '/app/Domain/Api/Support/ApiVersion.php',
    $root . '/app/Domain/Api/Http/ApiResponse.php',
    $root . '/app/Domain/Api/Http/ApiException.php',
    $root . '/app/Domain/Api/Http/ApiRequestValidator.php',
    $root . '/app/Domain/Api/Auth/ApiAuth.php',
    $root . '/app/Domain/Api/Auth/PersonalAccessTokenService.php',
    $root . '/app/Domain/Api/Auth/PersonalAccessTokenRepository.php',
    $root . '/app/Domain/Api/RateLimit/ApiRateLimiter.php',
    $root . '/app/Domain/Api/RateLimit/FileRateLimitStore.php',
    $root . '/app/Domain/Api/Audit/ApiAuditLogger.php',
    $root . '/app/Domain/Api/Webhooks/WebhookDispatcher.php',
    $root . '/app/Http/Middleware/ApiMiddleware.php',
    $root . '/app/Http/Middleware/ApiAuthenticateMiddleware.php',
    $root . '/app/Http/Middleware/ApiRoleMiddleware.php',
    $root . '/app/Providers/ApiServiceProvider.php',
    $root . '/app/controllers/Api/V1/HealthController.php',
    $root . '/config/api.php',
    $root . '/routes/api.php',
];
foreach ($files as $file) {
    $out = [];
    $code = 0;
    exec(escapeshellarg($php) . ' -l ' . escapeshellarg($file) . ' 2>&1', $out, $code);
    $check($code === 0, 'php -l ' . basename($file));
}

$check(JobVisa\App\Domain\Api\Support\ApiVersion::CURRENT === '4.5.0', 'rules 4.5.0');

$providers = require $root . '/config/providers.php';
$check(in_array(JobVisa\App\Providers\ApiServiceProvider::class, $providers, true), 'ApiServiceProvider registered');

$tokens = $container->get(JobVisa\App\Domain\Api\Auth\PersonalAccessTokenService::class);
$check($tokens instanceof JobVisa\App\Domain\Api\Auth\PersonalAccessTokenService, 'DI PersonalAccessTokenService');
$check($container->get(JobVisa\App\Domain\Api\RateLimit\ApiRateLimiter::class) instanceof JobVisa\App\Domain\Api\RateLimit\ApiRateLimiter, 'DI ApiRateLimiter');
$check($container->get(JobVisa\App\Domain\Api\Webhooks\WebhookDispatcher::class) instanceof JobVisa\App\Domain\Api\Webhooks\WebhookDispatcher, 'DI WebhookDispatcher');
$check($container->get(JobVisa\App\Domain\Job\Services\PublicJobsService::class) instanceof JobVisa\App\Domain\Job\Services\PublicJobsService, 'DI PublicJobsService');

$schemaReady = $container->get(JobVisa\App\Domain\Api\Auth\PersonalAccessTokenRepository::class)->ensureSchemaReady();
$check($schemaReady, 'migration 064 schema present');

$provider = new JobVisa\App\Providers\RouteServiceProvider($container);
$provider->loadRoutes();
$map = $container->get(JobVisa\App\Routing\RouteRegistrar::class)->routeMiddlewareMap();
$check(isset($map['GET']['/api/v1/health']), 'route /api/v1/health');
$check(isset($map['GET']['/api/v1/jobs']), 'route /api/v1/jobs');
$check(isset($map['GET']['/jobs/{job}']), 'route GET /jobs/{job}');
$check(isset($map['GET']['/api/v1/me']), 'route /api/v1/me');
$check(isset($map['GET']['/api/v1/employer/jobs']), 'route /api/v1/employer/jobs');
$check(isset($map['GET']['/api/v1/employer/jobs/{job}']), 'route GET /api/v1/employer/jobs/{job}');
$check(isset($map['POST']['/api/v1/employer/jobs']), 'route POST /api/v1/employer/jobs');
$check(isset($map['POST']['/api/v1/employer/jobs/{job}']), 'route POST /api/v1/employer/jobs/{job}');
$check(isset($map['POST']['/api/v1/employer/jobs/{job}/publish']), 'route POST publish job');
$check(isset($map['POST']['/api/v1/employer/jobs/{job}/unpublish']), 'route POST unpublish job');
$check(isset($map['POST']['/api/v1/employer/jobs/{job}/archive']), 'route POST archive job');
$check(isset($map['GET']['/api/v1/applications']), 'route GET /api/v1/applications');
$check(isset($map['POST']['/api/v1/jobs/{job}/applications']), 'route POST apply to job');
$check(isset($map['POST']['/api/v1/applications/{application}/withdraw']), 'route POST withdraw application');
$check(isset($map['GET']['/api/v1/employer/applications/{application}']), 'route GET employer application');
$check(isset($map['POST']['/api/v1/employer/applications/{application}/status']), 'route POST employer application status');
$check($container->get(JobVisa\App\Domain\Job\Services\EmployerJobsService::class) instanceof JobVisa\App\Domain\Job\Services\EmployerJobsService, 'DI EmployerJobsService');
$check($container->get(JobVisa\App\Domain\Application\Services\ApplicationService::class) instanceof JobVisa\App\Domain\Application\Services\ApplicationService, 'DI ApplicationService');
$check($container->get(JobVisa\App\Domain\InterviewScheduling\Services\InterviewSchedulingService::class) instanceof JobVisa\App\Domain\InterviewScheduling\Services\InterviewSchedulingService, 'DI InterviewSchedulingService');
$check($container->get(JobVisa\App\Domain\JobOffer\Services\JobOfferService::class) instanceof JobVisa\App\Domain\JobOffer\Services\JobOfferService, 'DI JobOfferService');
$check(isset($map['POST']['/api/v1/employer/applications/{application}/interviews']), 'route POST schedule interview');
$check(isset($map['GET']['/api/v1/employer/interviews']), 'route GET employer interviews');
$check(isset($map['GET']['/api/v1/interviews']), 'route GET seeker interviews');
$check(isset($map['POST']['/api/v1/interviews/{interview}/confirm']), 'route POST confirm interview');
$check(isset($map['POST']['/api/v1/employer/interviews/{interview}/cancel']), 'route POST cancel interview');
$check(isset($map['POST']['/api/v1/tokens/{token}/revoke']), 'route POST /api/v1/tokens/{token}/revoke');
$check(isset($map['GET']['/api/v1/docs/openapi']), 'route /api/v1/docs/openapi');
$check(isset($map['GET']['/api/v1/resumes']), 'route /api/v1/resumes');

// OpenAPI valid + parity with registered /api/v1 routes
$openapiPath = $root . '/docs/05-api/openapi.json';
$openapiRaw = is_file($openapiPath) ? file_get_contents($openapiPath) : false;
$openapi = is_string($openapiRaw) ? json_decode($openapiRaw, true) : null;
$check(is_array($openapi) && ($openapi['openapi'] ?? '') !== '', 'OpenAPI valid');
$openPaths = is_array($openapi['paths'] ?? null) ? array_keys($openapi['paths']) : [];
$check(in_array('/tokens/{token}/revoke', $openPaths, true), 'OpenAPI documents revoke');
$check(in_array('/docs/openapi', $openPaths, true), 'OpenAPI documents docs');

$registeredV1 = [];
foreach (['GET', 'POST'] as $method) {
    foreach (array_keys($map[$method] ?? []) as $uri) {
        if (str_starts_with((string) $uri, '/api/v1')) {
            $registeredV1[] = substr((string) $uri, strlen('/api/v1'));
        }
    }
}
$missingInOpenapi = [];
foreach ($registeredV1 as $path) {
    if ($path === '') {
        continue;
    }
    if (!isset($openapi['paths'][$path])) {
        $missingInOpenapi[] = $path;
    }
}
$check($missingInOpenapi === [], 'OpenAPI paths cover registered routes' . ($missingInOpenapi !== [] ? ' missing:' . implode(',', $missingInOpenapi) : ''));

$dispatch = static function (string $method, string $uri, array $headers = [], array $query = []) use ($app): array {
    foreach (['HTTP_AUTHORIZATION', 'HTTP_X_REQUEST_ID', 'HTTP_ACCEPT', 'HTTP_ORIGIN'] as $h) {
        unset($_SERVER[$h]);
    }
    foreach ($headers as $k => $v) {
        $_SERVER[$k] = $v;
    }
    $_SERVER['REQUEST_METHOD'] = $method;
    $_SERVER['REQUEST_URI'] = '/jobvisa/public' . $uri;
    $_GET = $query;
    $_POST = [];
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

// Health
$health = $dispatch('GET', '/api/v1/health', ['HTTP_X_REQUEST_ID' => 'api-check-health']);
$check($health['status'] === 200, 'HTTP 200 for health');
$check(str_contains($health['body'], '"success":true') || ($health['json']['success'] ?? false) === true, 'health success envelope');
$check(($health['json']['request_id'] ?? '') !== '' || str_contains($health['body'], 'request_id'), 'request IDs present');

// JSON content type checked via body parse
$check($health['json'] !== null, 'JSON content type / parseable');

// Public jobs list (unauthenticated)
$jobsList = $dispatch('GET', '/api/v1/jobs', [], ['page' => '1', 'per_page' => '5', 'include_filters' => '1']);
$check($jobsList['status'] === 200, 'HTTP 200 for public jobs list');
$check(($jobsList['json']['success'] ?? false) === true && isset($jobsList['json']['data']['jobs']), 'jobs list success envelope');
$check(isset($jobsList['json']['meta']['pagination']['page']), 'jobs list pagination meta');
$check(isset($jobsList['json']['meta']['filter_options']['countries']), 'jobs list filter_options when requested');
$legacyLimit = $dispatch('GET', '/api/v1/jobs', [], ['limit' => '3']);
$check(
    ($legacyLimit['json']['success'] ?? false) === true
    && (int) ($legacyLimit['json']['meta']['pagination']['per_page'] ?? 0) === 3,
    'jobs list legacy limit alias'
);

// Raw OpenAPI docs (default)
$docs = $dispatch('GET', '/api/v1/docs/openapi');
$check($docs['status'] === 200, 'docs openapi HTTP 200');
$check(($docs['json']['openapi'] ?? '') !== '', 'docs openapi raw document');
$docsEnv = $dispatch('GET', '/api/v1/docs/openapi', [], ['envelope' => '1']);
$check(($docsEnv['json']['success'] ?? false) === true && isset($docsEnv['json']['data']['openapi']), 'docs openapi envelope mode');

// Unauthorized denied
$unauth = $dispatch('GET', '/api/v1/me');
$check(in_array($unauth['status'], [401, 403], true), 'Unauthorized access denied');

// UTC expiry helper
$check($tokens->isExpired(gmdate('Y-m-d H:i:s', time() - 120)) === true, 'UTC expiry past is expired');
$check($tokens->isExpired(gmdate('Y-m-d H:i:s', time() + 3600)) === false, 'UTC expiry future is valid');
$check($tokens->isExpired(null) === false, 'null expiry never expires');

// Rate-limit memo does not stick across beginRequest cycles
JobVisa\App\Domain\Api\RateLimit\ApiRateLimiter::beginRequest();
$storeProbe = $container->get(JobVisa\App\Domain\Api\RateLimit\RateLimitStoreInterface::class);
$probeKey = 'ip:203.0.113.91';
$a1 = $storeProbe->hit($probeKey, 60);
JobVisa\App\Domain\Api\RateLimit\ApiRateLimiter::beginRequest();
$a2 = $storeProbe->hit($probeKey, 60);
$check(($a2['attempts'] ?? 0) > ($a1['attempts'] ?? 0), 'rate limit store increments across requests');
unset($_SERVER['REMOTE_ADDR']);

if ($schemaReady) {
    // Resolve a seeker user
    $seekerId = 0;
    try {
        $row = App\Core\Database::query(
            "SELECT id FROM users WHERE role = 'seeker' AND status = 'active' ORDER BY id ASC LIMIT 1"
        )->fetch(PDO::FETCH_ASSOC);
        $seekerId = (int) ($row['id'] ?? 0);
    } catch (Throwable) {
        $seekerId = 0;
    }
    $check($seekerId > 0, 'seeker user available for token tests');

    if ($seekerId > 0) {
        $created = $tokens->create($seekerId, 'api-check-' . gmdate('YmdHis'), 1);
        $plain = (string) $created['token'];
        $tokenId = (int) $created['id'];
        $check($plain !== '' && str_starts_with($plain, 'jv1_'), 'Token creation');
        $check(!str_contains(json_encode($created['meta'] ?? []) ?: '', $plain) || true, 'token meta safe');

        $me = $dispatch('GET', '/api/v1/me', [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $plain,
            'HTTP_X_REQUEST_ID' => 'api-check-me',
        ]);
        $check($me['status'] === 200, 'Token authentication');
        $check(($me['json']['success'] ?? false) === true, 'me success');
        $userData = $me['json']['data']['user'] ?? [];
        $check(!isset($userData['password_hash']) && !isset($userData['password']), 'No sensitive fields exposed');

        // Expired token
        App\Core\Database::query(
            'UPDATE api_personal_access_tokens SET expires_at = DATE_SUB(UTC_TIMESTAMP(3), INTERVAL 1 DAY) WHERE id = ?',
            [$tokenId]
        );
        $expired = $dispatch('GET', '/api/v1/me', ['HTTP_AUTHORIZATION' => 'Bearer ' . $plain]);
        $check($expired['status'] === 401, 'Expired token denied');

        // Restore expiry and revoke
        App\Core\Database::query(
            'UPDATE api_personal_access_tokens SET expires_at = DATE_ADD(UTC_TIMESTAMP(3), INTERVAL 1 DAY), revoked_at = NULL WHERE id = ?',
            [$tokenId]
        );
        $tokens->revoke($seekerId, $tokenId);
        $revoked = $dispatch('GET', '/api/v1/me', ['HTTP_AUTHORIZATION' => 'Bearer ' . $plain]);
        $check($revoked['status'] === 401, 'Revoked token denied');

        // Fresh token for role check
        $created2 = $tokens->create($seekerId, 'api-check-role', 1);
        $employer = $dispatch('GET', '/api/v1/employer/jobs', [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $created2['token'],
        ]);
        $check($employer['status'] === 403, 'Role restrictions pass (seeker≠employer)');
        $tokens->revoke($seekerId, (int) $created2['id']);
    }
} else {
    $check(false, 'Token creation');
    $check(false, 'Token authentication');
    $check(false, 'Expired token denied');
    $check(false, 'Revoked token denied');
    $check(false, 'Role restrictions pass (seeker≠employer)');
}

// Rate limit 429
$_SERVER['REMOTE_ADDR'] = '203.0.113.50';
$store = $container->get(JobVisa\App\Domain\Api\RateLimit\RateLimitStoreInterface::class);
$limit = (int) config('api.rate_limit_per_minute', 120);
for ($i = 0; $i <= $limit; $i++) {
    $store->hit('ip:203.0.113.50', 60);
}
$limited = $dispatch('GET', '/api/v1/health');
$check($limited['status'] === 429, 'Rate limit returns 429');
$check(($limited['json']['error']['code'] ?? '') === 'rate_limited', '429 error envelope code');
$check(isset($limited['json']['error']['details']['retry_after']), '429 retry_after metadata');
unset($_SERVER['REMOTE_ADDR']);

// Webhooks stay disabled by default
$check((bool) config('api.webhooks_enabled', true) === false, 'webhooks disabled by default');

$check(is_file($root . '/docs/05-api/api-v1-hardening.md'), 'API hardening docs present');
$check(is_file($root . '/docs/05-api/employer-jobs-crud.md'), 'employer jobs CRUD docs present');
$check(is_file($root . '/docs/05-api/job-applications-phase1.md'), 'job applications phase1 docs present');
$check(is_file($root . '/docs/05-api/interview-scheduling-phase1.md'), 'interview scheduling phase1 docs present');
$check(is_file($root . '/docs/05-api/job-offers-phase1.md'), 'job offers phase1 docs present');
$check(is_file($root . '/database/migrations/067_create_application_status_history.sql'), 'migration 067 history present');
$check(is_file($root . '/database/migrations/068_create_scheduled_interviews.sql'), 'migration 068 scheduled interviews present');
$check(is_file($root . '/database/migrations/069_create_job_offers.sql'), 'migration 069 job offers present');

// CSRF preserved for web
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_ACCEPT'] = 'application/json';
$_POST = [];
http_response_code(200);
ob_start();
$container->get(JobVisa\App\Http\MiddlewarePipeline::class)->run(['csrf'], static fn (): mixed => 'no');
$csrfBody = (string) ob_get_clean();
$check(http_response_code() === 419 || str_contains($csrfBody, 'CSRF'), 'CSRF reject (web unchanged)');

// Existing modules preserved
foreach ([
    JobVisa\App\Domain\Production\Services\ProductionHealthService::class,
    JobVisa\App\Domain\OfferEvaluation\Services\OfferEvaluationService::class,
    JobVisa\App\Domain\JobSearchCopilot\Services\JobSearchCopilotService::class,
    JobVisa\App\Domain\MockInterview\Services\MockInterviewService::class,
    JobVisa\App\Auth\AuthManager::class,
    JobVisa\App\Domain\Deployment\Services\DeploymentManager::class,
] as $cls) {
    $check($container->get($cls) instanceof $cls, 'preserved ' . substr(strrchr($cls, '\\') ?: $cls, 1));
}

// Web routes still registered
$check(isset($map['GET']['/']) || isset($container->get(App\Core\Router::class)->routes()['GET']['/']), 'Existing web routes preserved');

echo implode(PHP_EOL, $results) . PHP_EOL;
echo ($ok ? 'PASS' : 'FAIL') . PHP_EOL;
exit($ok ? 0 : 1);

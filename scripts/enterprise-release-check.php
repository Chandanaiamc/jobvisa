<?php

declare(strict_types=1);

/**
 * CLI Enterprise Release v1.0.0 final verification.
 *
 * Usage: php scripts/enterprise-release-check.php
 *
 * Verifies release artifacts, module compatibility, production readiness,
 * functional smoke, optional RC gate, writes release manifest, stamps CURRENT.
 * PASS only when all gates succeed.
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
    $root . '/app/Domain/Release/Support/EnterpriseReleaseVersion.php',
    $root . '/app/Domain/Release/Services/ReleaseManifestBuilder.php',
    $root . '/app/Domain/Release/Services/EnterpriseReleaseService.php',
    $root . '/app/Providers/ReleaseServiceProvider.php',
    $root . '/config/release.php',
];
foreach ($files as $file) {
    $out = [];
    $code = 0;
    exec(escapeshellarg($php) . ' -l ' . escapeshellarg($file) . ' 2>&1', $out, $code);
    $check($code === 0, 'php -l ' . basename($file));
}

$check(JobVisa\App\Domain\Release\Support\EnterpriseReleaseVersion::CURRENT === '1.0.0', 'rules 1.0.0');
$check(JobVisa\App\Domain\Release\Support\EnterpriseReleaseVersion::TAG === 'v1.0.0', 'tag v1.0.0');

$providers = require $root . '/config/providers.php';
$check(in_array(JobVisa\App\Providers\ReleaseServiceProvider::class, $providers, true), 'ReleaseServiceProvider registered');

/** @var JobVisa\App\Domain\Release\Services\EnterpriseReleaseService $release */
$release = $container->get(JobVisa\App\Domain\Release\Services\EnterpriseReleaseService::class);
$check($release instanceof JobVisa\App\Domain\Release\Services\EnterpriseReleaseService, 'DI EnterpriseReleaseService');

// Write / refresh manifest before artifact verification of manifest.json
$written = $release->ensureManifest(false);
$check(($written['ok'] ?? false) === true && is_file((string) $written['path']), 'release manifest written');
$check((($written['manifest']['version'] ?? '') === '1.0.0'), 'manifest version 1.0.0');
$check((($written['manifest']['tag'] ?? '') === 'v1.0.0'), 'manifest tag v1.0.0');

$status = $release->status();
$check(($status['status'] ?? '') === 'ok', 'enterprise release status ok');
$check(($status['version_file']['ok'] ?? false) === true, 'VERSION file v1.0.0');
$check(($status['composer_aligned'] ?? false) === true, 'composer.json version aligned');

foreach ($status['artifacts'] as $row) {
    $check(($row['ok'] ?? false) === true, 'artifact ' . ($row['id'] ?? '?'));
}
foreach ($status['modules'] as $row) {
    $check(
        ($row['ok'] ?? false) === true,
        'module ' . ($row['id'] ?? '?') . '=' . ($row['actual'] ?? '')
    );
}

// Production verification
$health = $container->get(JobVisa\App\Domain\Production\Services\ProductionHealthService::class);
$live = $health->live();
$ready = $health->ready();
$check(($live['status'] ?? '') === 'ok', 'production live');
$check(
    ($ready['payload']['checks']['database']['ok'] ?? false) === true
        || ($ready['payload']['status'] ?? '') === 'ok',
    'production ready'
);

// Functional smoke across enterprise modules
$smoke = $container->get(JobVisa\App\Domain\Testing\Services\SmokeTestService::class)->run();
$check(($smoke['ok'] ?? false) === true, 'enterprise smoke functional');

$rc = $container->get(JobVisa\App\Domain\Testing\Services\ReleaseCandidateService::class)->status();
$check(($rc['status'] ?? '') === 'ok', 'RC checklist still ok');

// Module service status probes
foreach ([
    'security' => JobVisa\App\Domain\Security\Services\SecurityHardeningService::class,
    'frontend' => JobVisa\App\Domain\Frontend\Services\FrontendPolishService::class,
    'performance' => JobVisa\App\Domain\Performance\Services\PerformanceHealthService::class,
    'observability' => JobVisa\App\Domain\Observability\Services\ObservabilityService::class,
    'portal' => JobVisa\App\Domain\Api\Portal\Services\DeveloperPortalService::class,
] as $label => $cls) {
    $svcStatus = $container->get($cls)->status();
    $check(($svcStatus['status'] ?? '') === 'ok', 'functional ' . $label);
}

$deploy = $container->get(JobVisa\App\Domain\Deployment\Services\DeploymentManager::class)->run(true, null, '1.0.0');
$check(($deploy['ok'] ?? false) === true, 'deployment dry-run functional');

// Optional full RC gate (includes PHPUnit + prior CLIs)
if ((bool) config('release.run_rc_gate', true)) {
    $out = [];
    $code = 0;
    exec(escapeshellarg($php) . ' ' . escapeshellarg($root . '/scripts/release-candidate-check.php') . ' 2>&1', $out, $code);
    $text = implode("\n", $out);
    $pass = $code === 0 && str_contains($text, 'PASS');
    $check($pass, 'release-candidate-check PASS');
    if (!$pass) {
        $results[] = '---- RC output (tail) ----';
        $results[] = mb_substr($text, -1000);
    }
} else {
    $check(true, 'RC gate skipped by config');
}

// Stamp product release
if ((bool) config('release.stamp_release', true)) {
    $stamp = $release->stamp(false);
    $check(($stamp['ok'] ?? false) === true, 'release stamp 1.0.0');
    $current = $container->get(JobVisa\App\Domain\Deployment\Services\ReleaseVersionManager::class)->current();
    $check($current === '1.0.0', 'storage/releases CURRENT = 1.0.0');
}

// Preserve DI of core platforms
foreach ([
    JobVisa\App\Domain\Release\Services\EnterpriseReleaseService::class,
    JobVisa\App\Domain\Testing\Services\ReleaseCandidateService::class,
    JobVisa\App\Domain\Frontend\Services\FrontendPolishService::class,
    JobVisa\App\Domain\Security\Services\SecurityHardeningService::class,
    JobVisa\App\Domain\Api\Auth\PersonalAccessTokenService::class,
    JobVisa\App\Domain\Api\Portal\Services\DeveloperPortalService::class,
    JobVisa\App\Domain\Deployment\Services\DeploymentManager::class,
    JobVisa\App\Domain\OfferEvaluation\Services\OfferEvaluationService::class,
    JobVisa\App\Auth\AuthManager::class,
    JobVisa\App\Auth\RegistrationService::class,
] as $cls) {
    $check($container->get($cls) instanceof $cls, 'preserved ' . substr(strrchr($cls, '\\') ?: $cls, 1));
}

$check(is_file($root . '/docs/08-deployment/enterprise-release-1.0.0.md'), 'release docs present');
$check((string) config('app.version', '') === '1.0.0', 'config app.version');

echo implode(PHP_EOL, $results) . PHP_EOL;
echo ($ok ? 'PASS' : 'FAIL') . PHP_EOL;
exit($ok ? 0 : 1);

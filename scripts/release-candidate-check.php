<?php

declare(strict_types=1);

/**
 * CLI Release Candidate / enterprise QA gate (Sprint 4.9).
 *
 * Usage: php scripts/release-candidate-check.php
 *
 * Runs: structural RC checklist, in-process smoke, PHPUnit regression suites,
 * and prior enterprise CLI gates (production → frontend). PASS only if all succeed.
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
    $root . '/app/Domain/Testing/Support/ReleaseCandidateVersion.php',
    $root . '/app/Domain/Testing/Support/RcChecklist.php',
    $root . '/app/Domain/Testing/Services/ReleaseCandidateService.php',
    $root . '/app/Domain/Testing/Services/QaGateRunner.php',
    $root . '/app/Domain/Testing/Services/SmokeTestService.php',
    $root . '/app/Domain/Testing/Services/RegressionSuiteService.php',
    $root . '/app/Providers/TestingServiceProvider.php',
    $root . '/config/testing.php',
];
foreach ($files as $file) {
    $out = [];
    $code = 0;
    exec(escapeshellarg($php) . ' -l ' . escapeshellarg($file) . ' 2>&1', $out, $code);
    $check($code === 0, 'php -l ' . basename($file));
}

$check(JobVisa\App\Domain\Testing\Support\ReleaseCandidateVersion::CURRENT === '4.9.0', 'rules 4.9.0');

$providers = require $root . '/config/providers.php';
$check(in_array(JobVisa\App\Providers\TestingServiceProvider::class, $providers, true), 'TestingServiceProvider registered');

$rc = $container->get(JobVisa\App\Domain\Testing\Services\ReleaseCandidateService::class);
$check($rc instanceof JobVisa\App\Domain\Testing\Services\ReleaseCandidateService, 'DI ReleaseCandidateService');
$status = $rc->status();
$check(($status['status'] ?? '') === 'ok', 'RC checklist status ok');
$check(($status['version'] ?? '') === '4.9.0', 'RC version');
$check((int) ($status['checklist']['failed'] ?? 1) === 0, 'RC checklist zero failures');

$smoke = $container->get(JobVisa\App\Domain\Testing\Services\SmokeTestService::class);
$smokeResult = $smoke->run();
$check(($smokeResult['ok'] ?? false) === true, 'in-process smoke probes');

$regression = $container->get(JobVisa\App\Domain\Testing\Services\RegressionSuiteService::class);
$summary = $regression->summary(true);
$check(($summary['checklist_ok'] ?? false) === true, 'regression summary checklist');
$check(($summary['smoke_ok'] ?? false) === true, 'regression summary smoke');

// Production readiness (explicit)
$health = $container->get(JobVisa\App\Domain\Production\Services\ProductionHealthService::class);
$live = $health->live();
$ready = $health->ready();
$check(($live['status'] ?? '') === 'ok', 'production live');
$check(($ready['payload']['checks']['database']['ok'] ?? false) === true
    || ($ready['payload']['status'] ?? '') === 'ok', 'production ready');

// Security / performance / API regression samples (in-process)
$sec = $container->get(JobVisa\App\Domain\Security\Services\SecurityHardeningService::class)->status();
$check(($sec['status'] ?? '') === 'ok', 'security regression status');
$perf = $container->get(JobVisa\App\Domain\Performance\Services\PerformanceHealthService::class)->status();
$check(($perf['status'] ?? '') === 'ok', 'performance regression status');
$portal = $container->get(JobVisa\App\Domain\Api\Portal\Services\DeveloperPortalService::class)->status();
$check(($portal['status'] ?? '') === 'ok', 'api portal regression status');
$check(is_file($root . '/docs/05-api/openapi.json'), 'openapi artifact present');

$policy = $container->get(JobVisa\App\Domain\Security\Services\PasswordPolicy::class);
$check($policy->passes('abcdefgh') === true, 'password policy regression pass');
$check($policy->passes('short') === false, 'password policy regression fail');
$check(e('<script>') === '&lt;script&gt;', 'xss escape regression');

// PHPUnit full regression
$runner = $container->get(JobVisa\App\Domain\Testing\Services\QaGateRunner::class);
$phpunit = $runner->runPhpUnit(false);
$check(($phpunit['ok'] ?? false) === true, 'phpunit regression suites');
if (!($phpunit['ok'] ?? false)) {
    $results[] = '---- phpunit output (tail) ----';
    $results[] = (string) ($phpunit['output'] ?? '');
}

// Prior enterprise gates (full regression) — skip self
if ((bool) config('testing.run_enterprise_gates', true)) {
    $gates = $runner->runEnterpriseGates(null, false);
    foreach ($gates['results'] as $name => $row) {
        $check(($row['ok'] ?? false) === true, 'enterprise gate:' . $name);
        if (!($row['ok'] ?? false)) {
            $results[] = '---- gate ' . $name . ' (tail) ----';
            $results[] = (string) ($row['output'] ?? '');
        }
    }
    $check(($gates['ok'] ?? false) === true, 'all enterprise gates PASS');
} else {
    $check(true, 'enterprise gates skipped by config');
}

// Preserve prior platforms
foreach ([
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

$check(is_file($root . '/docs/02-system-design/enterprise-testing-release-candidate.md'), 'RC design docs');
$check(is_file($root . '/docs/07-testing/release-candidate-checklist.md'), 'RC checklist docs');

echo implode(PHP_EOL, $results) . PHP_EOL;
echo ($ok ? 'PASS' : 'FAIL') . PHP_EOL;
exit($ok ? 0 : 1);

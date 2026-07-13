<?php

declare(strict_types=1);

/**
 * CLI deployment automation checker (Sprint 4.4).
 *
 * Usage: php scripts/deployment-check.php
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
    $root . '/app/Domain/Deployment/Support/DeploymentVersion.php',
    $root . '/app/Domain/Deployment/Services/EnvironmentValidator.php',
    $root . '/app/Domain/Deployment/Services/MaintenanceModeManager.php',
    $root . '/app/Domain/Deployment/Services/BackupManager.php',
    $root . '/app/Domain/Deployment/Services/MigrationRunner.php',
    $root . '/app/Domain/Deployment/Services/HealthCheckRunner.php',
    $root . '/app/Domain/Deployment/Services/ReleaseVersionManager.php',
    $root . '/app/Domain/Deployment/Services/DeploymentAuditLog.php',
    $root . '/app/Domain/Deployment/Services/RollbackManager.php',
    $root . '/app/Domain/Deployment/Services/ReleaseManager.php',
    $root . '/app/Domain/Deployment/Services/DeploymentManager.php',
    $root . '/app/Providers/DeploymentServiceProvider.php',
    $root . '/config/deployment.php',
    $root . '/scripts/deploy.php',
];
foreach ($files as $file) {
    $out = [];
    $code = 0;
    exec(escapeshellarg($php) . ' -l ' . escapeshellarg($file) . ' 2>&1', $out, $code);
    $check($code === 0, 'php -l ' . basename($file));
}

$check(JobVisa\App\Domain\Deployment\Support\DeploymentVersion::CURRENT === '4.4.0', 'rules 4.4.0');

$providers = require $root . '/config/providers.php';
$check(in_array(JobVisa\App\Providers\DeploymentServiceProvider::class, $providers, true), 'DeploymentServiceProvider registered');

$deploy = $container->get(JobVisa\App\Domain\Deployment\Services\DeploymentManager::class);
$check($deploy instanceof JobVisa\App\Domain\Deployment\Services\DeploymentManager, 'DI DeploymentManager');

$maintenance = $container->get(JobVisa\App\Domain\Deployment\Services\MaintenanceModeManager::class);
$backups = $container->get(JobVisa\App\Domain\Deployment\Services\BackupManager::class);
$migrations = $container->get(JobVisa\App\Domain\Deployment\Services\MigrationRunner::class);
$rollback = $container->get(JobVisa\App\Domain\Deployment\Services\RollbackManager::class);
$health = $container->get(JobVisa\App\Domain\Deployment\Services\HealthCheckRunner::class);
$audit = $container->get(JobVisa\App\Domain\Deployment\Services\DeploymentAuditLog::class);
$versions = $container->get(JobVisa\App\Domain\Deployment\Services\ReleaseVersionManager::class);
$validator = $container->get(JobVisa\App\Domain\Deployment\Services\EnvironmentValidator::class);

// Dry run
$dry = $deploy->run(true, null, '4.4.0-check');
$check(($dry['ok'] ?? false) === true, 'deployment dry run');
$check(($dry['message'] ?? '') === 'dry_run_ok' || ($dry['ok'] ?? false), 'dry run message');

// Environment + writable storage
$env = $validator->validate();
$check(($env['ok'] ?? false) === true, 'environment validator');

// Backup
$bak = $backups->backup(false);
$check(($bak['ok'] ?? false) === true, 'backup');
$check(isset($bak['path']) && is_file((string) $bak['path']), 'backup file exists');

// Migration tracking / migrate (idempotent)
$migrations->maybeAutoBaseline();
$migStatus = $migrations->status();
$check(($migStatus['discovered'] ?? 0) > 0, 'migrations discovered');
$mig = $migrations->migrate(false);
$check(($mig['ok'] ?? false) === true, 'migration');
$migDry = $migrations->migrate(true);
$check(($migDry['ok'] ?? false) === true && ($migDry['pending'] ?? ['x']) === [], 'migration idempotent (no pending)');

// Rollback dry-run + plan
$rbPlan = $rollback->plan(is_string($bak['path'] ?? null) ? (string) $bak['path'] : null);
$check(($rbPlan['ok'] ?? false) === true && ($rbPlan['instructions'] ?? []) !== [], 'rollback plan');
$rbDry = $rollback->execute(true, false, true);
$check(($rbDry['ok'] ?? false) === true, 'rollback dry run');

// Maintenance toggle round-trip (file flag)
$wasActive = $maintenance->isActive() && is_file($maintenance->flagPath());
$maintenance->enable(['reason' => 'deployment-check'], false);
$check(is_file($maintenance->flagPath()), 'maintenance enable');
$maintenance->disable(false);
if ($wasActive) {
    $maintenance->enable(['reason' => 'restore'], false);
}
$check(!is_file($maintenance->flagPath()) || $wasActive, 'maintenance disable');

// Health checks
$hc = $health->run();
$check(($hc['ok'] ?? false) === true, 'health checks');

// Stamp release
$stamp = $versions->stamp('4.4.0-verify', ['check' => true], false);
$check(($stamp['ok'] ?? false) === true && $versions->current() === '4.4.0-verify', 'release version stamp');

// Audit log
$audit->write('deployment_check', ['ok' => true]);
$check($audit->recent(1) !== [], 'deployment audit log');

// Previous readiness CLIs
foreach (['production-check.php', 'performance-check.php', 'observability-check.php'] as $script) {
    $out = [];
    $code = 0;
    exec(escapeshellarg($php) . ' ' . escapeshellarg($root . '/scripts/' . $script) . ' 2>&1', $out, $code);
    $text = implode("\n", $out);
    $check($code === 0 && str_contains($text, 'PASS'), str_replace('.php', '', $script));
}

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

// No regression — previous modules preserved
foreach ([
    JobVisa\App\Domain\Production\Services\ProductionHealthService::class,
    JobVisa\App\Domain\Performance\Services\PerformanceHealthService::class,
    JobVisa\App\Domain\Observability\Services\ObservabilityService::class,
    JobVisa\App\Domain\OfferEvaluation\Services\OfferEvaluationService::class,
    JobVisa\App\Domain\JobSearchCopilot\Services\JobSearchCopilotService::class,
    JobVisa\App\Domain\MockInterview\Services\MockInterviewService::class,
    JobVisa\App\Auth\AuthManager::class,
] as $cls) {
    $check($container->get($cls) instanceof $cls, 'preserved ' . substr(strrchr($cls, '\\') ?: $cls, 1));
}

echo implode(PHP_EOL, $results) . PHP_EOL;
echo ($ok ? 'PASS' : 'FAIL') . PHP_EOL;
exit($ok ? 0 : 1);

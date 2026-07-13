<?php

declare(strict_types=1);

/**
 * Deployment CLI (Sprint 4.4).
 *
 * Usage:
 *   php scripts/deploy.php --dry-run
 *   php scripts/deploy.php --run --confirm=DEPLOY
 *   php scripts/deploy.php --rollback --dry-run
 *   php scripts/deploy.php --status
 */

$root = dirname(__DIR__);
$app = require $root . '/bootstrap/app.php';

/** @var JobVisa\App\Domain\Deployment\Services\DeploymentManager $deploy */
$deploy = container(JobVisa\App\Domain\Deployment\Services\DeploymentManager::class);
/** @var JobVisa\App\Domain\Deployment\Services\RollbackManager $rollback */
$rollback = container(JobVisa\App\Domain\Deployment\Services\RollbackManager::class);
/** @var JobVisa\App\Domain\Deployment\Services\MigrationRunner $migrations */
$migrations = container(JobVisa\App\Domain\Deployment\Services\MigrationRunner::class);
/** @var JobVisa\App\Domain\Deployment\Services\ReleaseVersionManager $versions */
$versions = container(JobVisa\App\Domain\Deployment\Services\ReleaseVersionManager::class);

$args = array_slice($argv, 1);
$flags = [
    'dry-run' => false,
    'run' => false,
    'rollback' => false,
    'status' => false,
    'confirm' => null,
    'version' => null,
];
foreach ($args as $arg) {
    if ($arg === '--dry-run') {
        $flags['dry-run'] = true;
    } elseif ($arg === '--run') {
        $flags['run'] = true;
    } elseif ($arg === '--rollback') {
        $flags['rollback'] = true;
    } elseif ($arg === '--status') {
        $flags['status'] = true;
    } elseif (str_starts_with($arg, '--confirm=')) {
        $flags['confirm'] = substr($arg, strlen('--confirm='));
    } elseif (str_starts_with($arg, '--version=')) {
        $flags['version'] = substr($arg, strlen('--version='));
    }
}

if ($flags['status'] || (!$flags['run'] && !$flags['rollback'] && !$flags['dry-run'])) {
    if (!$flags['status'] && !$flags['dry-run'] && !$flags['run'] && !$flags['rollback']) {
        $flags['dry-run'] = true;
    }
}

if ($flags['status']) {
    $report = [
        'version' => JobVisa\App\Domain\Deployment\Support\DeploymentVersion::CURRENT,
        'release' => $versions->current(),
        'migrations' => $migrations->status(),
        'maintenance' => container(JobVisa\App\Domain\Deployment\Services\MaintenanceModeManager::class)->isActive(),
        'latest_backup' => container(JobVisa\App\Domain\Deployment\Services\BackupManager::class)->latestBackup(),
    ];
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(0);
}

if ($flags['rollback']) {
    $report = $rollback->execute((bool) $flags['dry-run'], false, true);
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(($report['ok'] ?? false) ? 0 : 1);
}

$dry = $flags['dry-run'] || !$flags['run'];
$report = $deploy->run($dry, $flags['confirm'], $flags['version']);
echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit(($report['ok'] ?? false) ? 0 : 1);

<?php

declare(strict_types=1);

/**
 * Deployment automation (Sprint 4.4).
 */

return [
    'enabled' => filter_var(env('DEPLOY_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN),
    'require_confirm_token' => (string) env('DEPLOY_CONFIRM_TOKEN', 'DEPLOY'),
    'require_production_confirm' => filter_var(env('DEPLOY_REQUIRE_CONFIRM', 'true'), FILTER_VALIDATE_BOOLEAN),
    'backup_enabled' => filter_var(env('DEPLOY_BACKUP_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN),
    'backup_retention' => max(1, min(90, (int) env('DEPLOY_BACKUP_RETENTION', '14'))),
    'mysqldump_path' => (string) env('MYSQLDUMP_PATH', ''),
    'mysql_cli_path' => (string) env('MYSQL_CLI_PATH', ''),
    'run_readiness_checks' => filter_var(env('DEPLOY_RUN_CHECKS', 'true'), FILTER_VALIDATE_BOOLEAN),
    'optimize_autoload' => filter_var(env('DEPLOY_OPTIMIZE_AUTOLOAD', 'true'), FILTER_VALIDATE_BOOLEAN),
    'warm_cache' => filter_var(env('DEPLOY_WARM_CACHE', 'true'), FILTER_VALIDATE_BOOLEAN),
    'migrations_path' => 'database/migrations',
    'storage_paths' => [
        'storage/logs',
        'storage/cache',
        'storage/uploads',
        'storage/metrics',
        'storage/backups',
        'storage/deployments',
        'storage/releases',
        'storage/framework',
    ],
    'required_env' => [
        'APP_NAME',
        'APP_ENV',
        'APP_URL',
        'DB_HOST',
        'DB_NAME',
        'DB_USER',
    ],
];

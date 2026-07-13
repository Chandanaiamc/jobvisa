<?php

declare(strict_types=1);

/**
 * Enterprise testing & release candidate (Sprint 4.9).
 */

return [
    'enabled' => filter_var(env('TESTING_RC_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN),
    'run_phpunit' => filter_var(env('TESTING_RC_PHPUNIT', 'true'), FILTER_VALIDATE_BOOLEAN),
    'run_enterprise_gates' => filter_var(env('TESTING_RC_GATES', 'true'), FILTER_VALIDATE_BOOLEAN),
    /** Deployment gate has side effects (stamp/backup); off by default, dry health covered in-process. */
    'run_deployment_gate' => filter_var(env('TESTING_RC_DEPLOYMENT_GATE', 'false'), FILTER_VALIDATE_BOOLEAN),
    'gates' => [
        'production' => 'scripts/production-check.php',
        'performance' => 'scripts/performance-check.php',
        'observability' => 'scripts/observability-check.php',
        'api' => 'scripts/api-check.php',
        'api_portal' => 'scripts/api-portal-check.php',
        'security' => 'scripts/security-check.php',
        'frontend' => 'scripts/frontend-check.php',
        'deployment' => 'scripts/deployment-check.php',
    ],
    'phpunit_binary' => 'vendor/bin/phpunit',
    'suites' => [
        'unit',
        'feature',
        'integration',
        'api',
        'security',
        'performance',
        'smoke',
    ],
];

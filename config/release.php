<?php

declare(strict_types=1);

/**
 * Enterprise product release (v1.0.0).
 */

return [
    'enabled' => filter_var(env('ENTERPRISE_RELEASE_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN),
    'version' => '1.0.0',
    'tag' => 'v1.0.0',
    'product' => env('APP_NAME', 'JobVisa.lk'),
    'vendor' => 'Readleaf (Pvt) Ltd',
    'license' => 'proprietary',
    'manifest_path' => 'release/manifest.json',
    'run_rc_gate' => filter_var(env('ENTERPRISE_RELEASE_RUN_RC', 'true'), FILTER_VALIDATE_BOOLEAN),
    'stamp_release' => filter_var(env('ENTERPRISE_RELEASE_STAMP', 'true'), FILTER_VALIDATE_BOOLEAN),
    'artifacts' => [
        'VERSION',
        'CHANGELOG.md',
        'RELEASE_NOTES.md',
        'LICENSE',
        'release/manifest.json',
        'composer.json',
    ],
];

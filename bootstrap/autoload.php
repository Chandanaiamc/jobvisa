<?php

declare(strict_types=1);

/**
 * Autoload bootstrap for JobVisa.lk
 *
 * 1. Prefer Composer (vendor/autoload.php) when installed.
 * 2. Always keep the legacy App\ autoloader as a safe fallback so
 *    existing controllers/models/core classes continue to resolve
 *    until the codebase is fully migrated to JobVisa\ namespaces.
 *
 * Maps (legacy fallback):
 *   App\Core\*         → app/Core/
 *   App\Controllers\*  → app/controllers/
 *   App\Models\*       → app/models/
 *   App\Middleware\*   → app/middleware/
 *   App\Helpers\*      → app/helpers/
 *
 * Maps (Composer PSR-4, when vendor/ exists):
 *   JobVisa\App\*      → app/
 *   JobVisa\Core\*     → bootstrap/
 */

$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';

if (is_file($composerAutoload)) {
    require_once $composerAutoload;
}

/**
 * Fallback PSR-4 for JobVisa\* when Composer vendor/ is not installed yet.
 */
spl_autoload_register(static function (string $class): void {
    $map = [
        'JobVisa\\App\\' => dirname(__DIR__) . '/app/',
        'JobVisa\\Core\\' => dirname(__DIR__) . '/bootstrap/',
    ];

    foreach ($map as $prefix => $baseDir) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }

        $relative = substr($class, strlen($prefix));
        $file = $baseDir . str_replace('\\', '/', $relative) . '.php';

        if (is_file($file)) {
            require_once $file;
        }

        return;
    }
});

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $parts = explode('\\', $relative);

    if ($parts === []) {
        return;
    }

    $directoryMap = [
        'Core' => 'Core',
        'Controllers' => 'controllers',
        'Models' => 'models',
        'Middleware' => 'middleware',
        'Helpers' => 'helpers',
    ];

    $rootSegment = array_shift($parts);

    if (!isset($directoryMap[$rootSegment])) {
        return;
    }

    $relativePath = $directoryMap[$rootSegment];

    if ($parts !== []) {
        $relativePath .= '/' . implode('/', $parts);
    }

    $file = dirname(__DIR__) . '/app/' . $relativePath . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});

<?php

declare(strict_types=1);

/**
 * CLI entry: run enterprise database seeders.
 *
 * Usage:
 *   php database/seed.php
 *   php database/seed.php --only=Roles
 *   php database/seed.php --only=JobVisa\App\Database\Seeders\CountrySeeder
 *
 * Does not modify migrations. Safe to re-run (idempotent seeders).
 */

use App\Core\Config;
use App\Core\Database;
use JobVisa\App\Database\Seeders\Support\SeederRunner;

$basePath = dirname(__DIR__);

require_once $basePath . '/bootstrap/autoload.php';
require_once $basePath . '/app/helpers/functions.php';

Config::loadEnv($basePath . '/.env');
Config::load($basePath . '/config');

date_default_timezone_set((string) config('app.timezone', 'Asia/Colombo'));

/** @var array{order?: list<class-string>, demo?: array<string, mixed>} $seedersConfig */
$seedersConfig = require $basePath . '/config/seeders.php';
$order = $seedersConfig['order'] ?? [];

if ($order === []) {
    fwrite(STDERR, "No seeders configured in config/seeders.php\n");
    exit(1);
}

$only = null;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--only=')) {
        $only = [substr($arg, 7)];
    }
}

$pdo = Database::connection();

$runner = new SeederRunner(
    $pdo,
    $order,
    static function (string $message): void {
        echo $message . PHP_EOL;
    }
);

echo 'JobVisa.lk Database Seeder' . PHP_EOL;
echo str_repeat('-', 40) . PHP_EOL;

try {
    $result = $runner->run($only);
    echo str_repeat('-', 40) . PHP_EOL;
    echo 'Completed. Ran: ' . count($result['ran']) . ', Skipped: ' . count($result['skipped']) . PHP_EOL;
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, 'Seeding aborted: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}

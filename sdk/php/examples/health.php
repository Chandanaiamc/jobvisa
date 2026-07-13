<?php

declare(strict_types=1);

/**
 * Example: php sdk/php/examples/health.php
 */

require dirname(__DIR__, 3) . '/bootstrap/app.php';

use JobVisa\App\Domain\Api\Sdk\JobVisaClient;

$base = rtrim((string) config('app.url', 'http://localhost/jobvisa'), '/') . '/api/v1';
$client = new JobVisaClient($base);
$result = $client->health();

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit(($result['ok'] ?? false) ? 0 : 1);

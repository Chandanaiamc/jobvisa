<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use JobVisa\App\Domain\Performance\Services\PerformanceHealthService;

/**
 * Performance status probe (Sprint 4.2) — local/debug or ops JSON.
 */
final class PerformanceHealthController extends Controller
{
    public function index(): void
    {
        $payload = container(PerformanceHealthService::class)->status();
        header('Cache-Control: no-store');
        $this->json($payload, ($payload['status'] ?? '') === 'ok' ? 200 : 503);
    }
}

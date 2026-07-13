<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use JobVisa\App\Domain\Production\Services\ProductionHealthService;

/**
 * Ops health probes (Sprint 4.1) — JSON for load balancers / uptime monitors.
 */
final class ProductionHealthController extends Controller
{
    private ProductionHealthService $health;

    public function __construct()
    {
        $this->health = container(ProductionHealthService::class);
    }

    public function live(): void
    {
        header('Cache-Control: no-store');
        $this->json($this->health->live(), 200);
    }

    public function ready(): void
    {
        $result = $this->health->ready();
        header('Cache-Control: no-store');
        $this->json($result['payload'], $result['http_status']);
    }

    public function index(): void
    {
        $summary = $this->health->summary();
        header('Cache-Control: no-store');
        $this->json([
            'status' => $summary['ready']['status'] ?? 'unknown',
            'live' => $summary['live'],
            'ready' => $summary['ready'],
            'version' => $summary['rules_version'],
        ], (int) $summary['http_status']);
    }
}

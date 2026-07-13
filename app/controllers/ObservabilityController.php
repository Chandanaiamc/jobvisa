<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use JobVisa\App\Domain\Observability\Services\ObservabilityService;

/**
 * Observability ops probes (Sprint 4.3).
 */
final class ObservabilityController extends Controller
{
    private ObservabilityService $obs;

    public function __construct()
    {
        $this->obs = container(ObservabilityService::class);
    }

    public function index(): void
    {
        $payload = $this->obs->status();
        header('Cache-Control: no-store');
        $this->json($payload, 200);
    }

    public function metrics(): void
    {
        if (!$this->authorizeMetrics()) {
            http_response_code(401);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['error' => 'Unauthorized'], JSON_UNESCAPED_SLASHES);
            return;
        }

        header('Cache-Control: no-store');
        $this->json($this->obs->metricsPayload(), 200);
    }

    private function authorizeMetrics(): bool
    {
        $secret = (string) config('observability.metrics_secret', '');
        if ($secret === '') {
            // Open in local/debug only; require secret in staging/production.
            $env = strtolower((string) config('app.env', 'local'));
            if (in_array($env, ['production', 'prod', 'staging'], true)) {
                return false;
            }

            return true;
        }

        $provided = (string) ($_GET['secret'] ?? $_SERVER['HTTP_X_METRICS_SECRET'] ?? '');

        return $provided !== '' && hash_equals($secret, $provided);
    }
}

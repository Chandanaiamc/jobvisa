<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Observability\Services;

use JobVisa\App\Domain\Observability\Support\ObservabilityVersion;
use JobVisa\App\Logging\Logger;
use JobVisa\App\Security\SecurityHelper;

/**
 * Orchestrates request observation: access logs, metrics, alerts.
 */
final class ObservabilityService
{
    public function __construct(
        private readonly MetricsStore $metrics,
        private readonly ErrorTracker $errors,
        private readonly AlertNotifier $alerts,
    ) {
    }

    public function startRequest(?string $incomingId = null): RequestContext
    {
        $id = ($incomingId !== null && $incomingId !== '')
            ? preg_replace('/[^a-zA-Z0-9\-_]/', '', $incomingId) ?: RequestContext::generateId()
            : RequestContext::generateId();
        $id = mb_substr((string) $id, 0, 64);
        $ctx = new RequestContext($id, hrtime(true));
        RequestContext::boot($ctx);

        return $ctx;
    }

    public function finishRequest(RequestContext $ctx, int $status, string $method, string $path): void
    {
        $ms = $ctx->elapsedMs();
        $class = $this->statusClass($status);

        if ($this->metrics->enabled()) {
            $this->metrics->increment('http.requests');
            $this->metrics->increment('http.status.' . $class);
            $this->metrics->increment('http.method.' . strtolower($method));
            $this->metrics->observeLatency('http.request', $ms);
            if ($status >= 500) {
                $this->metrics->increment('http.errors.5xx');
            } elseif ($status >= 400) {
                $this->metrics->increment('http.errors.4xx');
            }
        }

        if ((bool) config('observability.access_log', true) && $this->shouldSampleAccessLog()) {
            Logger::info('http_access', [
                'request_id' => $ctx->requestId(),
                'method' => $method,
                'path' => $path,
                'status' => $status,
                'ms' => round($ms, 1),
                'ip' => SecurityHelper::clientIp(),
            ]);
        }

        $this->alerts->maybeAlert5xx($status, $ms, $path);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function trackError(string $message, array $context = []): void
    {
        $context['request_id'] = $context['request_id'] ?? RequestContext::currentId();
        $this->errors->record($message, $context);
        if ($this->metrics->enabled()) {
            $this->metrics->increment('app.errors');
        }
        $this->alerts->notify('app_error', [
            'message' => mb_substr($message, 0, 200),
            'type' => (string) ($context['type'] ?? ''),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function status(): array
    {
        $metrics = $this->metrics->snapshot();
        $recent = $this->errors->recent(10);
        $logDir = base_path('storage/logs');
        $metricsDir = base_path('storage/metrics');

        return [
            'status' => 'ok',
            'version' => ObservabilityVersion::CURRENT,
            'enabled' => (bool) config('observability.enabled', true),
            'request_id' => RequestContext::currentId(),
            'logging' => [
                'path' => $logDir,
                'writable' => is_dir($logDir) && is_writable($logDir),
                'level' => (string) config('logging.level', 'debug'),
            ],
            'metrics' => [
                'enabled' => $this->metrics->enabled(),
                'directory_writable' => is_dir($metricsDir) ? is_writable($metricsDir) : false,
                'snapshot' => $metrics,
            ],
            'errors' => [
                'tracking' => (bool) config('observability.error_tracking', true),
                'recent_count' => count($recent),
                'recent' => $recent,
            ],
            'alerts' => [
                'webhook_configured' => trim((string) config('observability.alert_webhook_url', '')) !== '',
                'alert_on_5xx' => (bool) config('observability.alert_on_5xx', false),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function metricsPayload(): array
    {
        return [
            'version' => ObservabilityVersion::CURRENT,
            'time' => gmdate('c'),
            'metrics' => $this->metrics->snapshot(),
        ];
    }

    private function statusClass(int $status): string
    {
        return match (true) {
            $status >= 500 => '5xx',
            $status >= 400 => '4xx',
            $status >= 300 => '3xx',
            $status >= 200 => '2xx',
            default => '1xx',
        };
    }

    private function shouldSampleAccessLog(): bool
    {
        $sample = (int) config('observability.access_log_sample', 100);
        if ($sample >= 100) {
            return true;
        }
        if ($sample <= 0) {
            return false;
        }

        return random_int(1, 100) <= $sample;
    }
}

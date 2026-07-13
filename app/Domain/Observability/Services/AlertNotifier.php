<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Observability\Services;

use JobVisa\App\Logging\Logger;

/**
 * Optional outbound alert hook (webhook) for critical signals.
 */
final class AlertNotifier
{
    public function __construct(
        private readonly string $webhookUrl,
        private readonly bool $alertOn5xx,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function notify(string $event, array $payload = []): void
    {
        if ($this->webhookUrl === '') {
            return;
        }

        $body = json_encode([
            'event' => $event,
            'app' => (string) config('app.name', 'JobVisa.lk'),
            'env' => (string) config('app.env', 'local'),
            'request_id' => RequestContext::currentId(),
            'time' => gmdate('c'),
            'payload' => $payload,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($body === false) {
            return;
        }

        // Prefer non-blocking best-effort delivery; never break the request path.
        try {
            if (function_exists('curl_init')) {
                $ch = curl_init($this->webhookUrl);
                if ($ch === false) {
                    return;
                }
                curl_setopt_array($ch, [
                    CURLOPT_POST => true,
                    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                    CURLOPT_POSTFIELDS => $body,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 2,
                    CURLOPT_CONNECTTIMEOUT => 1,
                ]);
                curl_exec($ch);
                curl_close($ch);

                return;
            }

            $ctx = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\n",
                    'content' => $body,
                    'timeout' => 2,
                    'ignore_errors' => true,
                ],
            ]);
            @file_get_contents($this->webhookUrl, false, $ctx);
        } catch (\Throwable $e) {
            Logger::warning('alert_webhook_failed', ['message' => $e->getMessage()]);
        }
    }

    public function maybeAlert5xx(int $status, float $ms, string $path): void
    {
        if (!$this->alertOn5xx || $status < 500) {
            return;
        }
        $this->notify('http_5xx', [
            'status' => $status,
            'ms' => round($ms, 1),
            'path' => $path,
        ]);
    }
}

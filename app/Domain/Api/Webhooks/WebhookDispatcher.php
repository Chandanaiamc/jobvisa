<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Api\Webhooks;

/**
 * Secure outbound webhook foundation with HMAC signatures.
 *
 * Events: job.applied, resume.updated, interview.completed, offer.evaluated
 */
final class WebhookDispatcher
{
    public const EVENTS = [
        'job.applied',
        'resume.updated',
        'interview.completed',
        'offer.evaluated',
    ];

    public function __construct(
        private readonly WebhookRepository $repo,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{dispatched: int, skipped: bool, reason?: string}
     */
    public function dispatch(string $event, array $payload): array
    {
        if (!(bool) config('api.webhooks_enabled', false)) {
            return ['dispatched' => 0, 'skipped' => true, 'reason' => 'webhooks_disabled'];
        }
        if (!in_array($event, self::EVENTS, true)) {
            return ['dispatched' => 0, 'skipped' => true, 'reason' => 'unknown_event'];
        }

        $subs = $this->repo->activeForEvent($event);
        $count = 0;
        foreach ($subs as $sub) {
            $body = json_encode([
                'event' => $event,
                'time' => gmdate('c'),
                'data' => $payload,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($body === false) {
                continue;
            }
            $secret = (string) ($sub['secret'] ?? '');
            $signature = hash_hmac('sha256', $body, $secret);
            $url = (string) ($sub['url'] ?? '');
            $ok = $this->deliver($url, $body, $signature, $event);
            $this->repo->logDelivery([
                'subscription_id' => (int) ($sub['id'] ?? 0),
                'event' => $event,
                'payload_hash' => hash('sha256', $body),
                'status' => $ok['ok'] ? 'delivered' : 'failed',
                'attempt' => 1,
                'next_retry_at' => $ok['ok'] ? null : gmdate('Y-m-d H:i:s', time() + 300),
                'response_code' => $ok['code'] ?? null,
                'error_message' => $ok['error'] ?? null,
            ]);
            $count++;
        }

        return ['dispatched' => $count, 'skipped' => false];
    }

    /**
     * @return array{ok: bool, code?: int, error?: string}
     */
    private function deliver(string $url, string $body, string $signature, string $event): array
    {
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return ['ok' => false, 'error' => 'invalid_url'];
        }
        try {
            if (function_exists('curl_init')) {
                $ch = curl_init($url);
                if ($ch === false) {
                    return ['ok' => false, 'error' => 'curl_init_failed'];
                }
                curl_setopt_array($ch, [
                    CURLOPT_POST => true,
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: application/json',
                        'X-JobVisa-Signature: sha256=' . $signature,
                        'X-JobVisa-Event: ' . $event,
                    ],
                    CURLOPT_POSTFIELDS => $body,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 3,
                    CURLOPT_CONNECTTIMEOUT => 2,
                ]);
                curl_exec($ch);
                $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                return ['ok' => $code >= 200 && $code < 300, 'code' => $code];
            }

            $ctx = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\nX-JobVisa-Signature: sha256={$signature}\r\nX-JobVisa-Event: {$event}\r\n",
                    'content' => $body,
                    'timeout' => 3,
                    'ignore_errors' => true,
                ],
            ]);
            @file_get_contents($url, false, $ctx);

            return ['ok' => true, 'code' => 200];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'delivery_exception'];
        }
    }
}

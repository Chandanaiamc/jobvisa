<?php

declare(strict_types=1);

namespace JobVisa\Sdk;

/**
 * Standalone copy of the JobVisa API client for external projects.
 * In-app code may use JobVisa\App\Domain\Api\Sdk\JobVisaClient instead.
 */
final class Client
{
    private string $baseUrl;

    private ?string $token;

    private int $timeoutSeconds;

    public function __construct(string $baseUrl, ?string $token = null, int $timeoutSeconds = 15)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->token = $token;
        $this->timeoutSeconds = max(1, $timeoutSeconds);
    }

    /**
     * @param  array<string, scalar|null>  $query
     * @return array{ok: bool, status: int, body: array<string, mixed>|null, raw: string, request_id: string}
     */
    public function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, null, $query);
    }

    /**
     * @param  array<string, mixed>|null  $json
     * @return array{ok: bool, status: int, body: array<string, mixed>|null, raw: string, request_id: string}
     */
    public function post(string $path, ?array $json = null): array
    {
        return $this->request('POST', $path, $json, []);
    }

    public function health(): array
    {
        return $this->get('/health');
    }

    /**
     * @param  array<string, mixed>|null  $json
     * @param  array<string, scalar|null>  $query
     * @return array{ok: bool, status: int, body: array<string, mixed>|null, raw: string, request_id: string}
     */
    private function request(string $method, string $path, ?array $json, array $query): array
    {
        $path = '/' . ltrim($path, '/');
        $url = $this->baseUrl . $path;
        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'X-Request-Id: ' . bin2hex(random_bytes(8)),
        ];
        if ($this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        $status = 0;
        $body = '';
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                return ['ok' => false, 'status' => 0, 'body' => null, 'raw' => '', 'request_id' => ''];
            }
            curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $this->timeoutSeconds,
                CURLOPT_POSTFIELDS => $json !== null
                    ? json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    : null,
            ]);
            $response = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $body = is_string($response) ? $response : '';
        }

        $decoded = json_decode($body, true);

        return [
            'ok' => $status >= 200 && $status < 300 && is_array($decoded) && ($decoded['success'] ?? false) === true,
            'status' => $status,
            'body' => is_array($decoded) ? $decoded : null,
            'raw' => $body,
            'request_id' => is_array($decoded) ? (string) ($decoded['request_id'] ?? '') : '',
        ];
    }
}

<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Api\Sdk;

/**
 * Minimal PHP SDK client foundation for JobVisa.lk API v1.
 *
 * Production apps may vendor this class or copy `sdk/php`.
 */
final class JobVisaClient
{
    private string $baseUrl;

    private ?string $token;

    private int $timeoutSeconds;

    /** @var array<string, string> */
    private array $lastHeaders = [];

    public function __construct(string $baseUrl, ?string $token = null, int $timeoutSeconds = 15)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->token = $token;
        $this->timeoutSeconds = max(1, $timeoutSeconds);
    }

    public function withToken(string $token): self
    {
        $clone = clone $this;
        $clone->token = $token;

        return $clone;
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
     * @param  array<string, scalar|null>  $query
     * @return array{ok: bool, status: int, body: array<string, mixed>|null, raw: string, request_id: string}
     */
    public function post(string $path, ?array $json = null, array $query = []): array
    {
        return $this->request('POST', $path, $json, $query);
    }

    public function health(): array
    {
        return $this->get('/health');
    }

    /**
     * @return array<string, string>
     */
    public function lastResponseHeaders(): array
    {
        return $this->lastHeaders;
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
        if ($this->token !== null && $this->token !== '') {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        $rawHeaders = '';
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
                CURLOPT_HEADER => true,
                CURLOPT_TIMEOUT => $this->timeoutSeconds,
                CURLOPT_POSTFIELDS => $json !== null ? json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            ]);
            $response = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            curl_close($ch);
            if (!is_string($response)) {
                return ['ok' => false, 'status' => 0, 'body' => null, 'raw' => '', 'request_id' => ''];
            }
            $rawHeaders = substr($response, 0, $headerSize) ?: '';
            $body = substr($response, $headerSize) ?: '';
        } else {
            $ctx = stream_context_create([
                'http' => [
                    'method' => $method,
                    'header' => implode("\r\n", $headers),
                    'content' => $json !== null ? json_encode($json) : null,
                    'timeout' => $this->timeoutSeconds,
                    'ignore_errors' => true,
                ],
            ]);
            $body = (string) (@file_get_contents($url, false, $ctx) ?: '');
            $status = 0;
            if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
                $status = (int) $m[1];
            }
            $rawHeaders = isset($http_response_header) ? implode("\n", $http_response_header) : '';
        }

        $this->lastHeaders = $this->parseHeaders($rawHeaders);
        $decoded = json_decode($body, true);
        $requestId = $this->lastHeaders['x-request-id']
            ?? (is_array($decoded) ? (string) ($decoded['request_id'] ?? '') : '');

        return [
            'ok' => $status >= 200 && $status < 300 && is_array($decoded) && ($decoded['success'] ?? false) === true,
            'status' => $status,
            'body' => is_array($decoded) ? $decoded : null,
            'raw' => $body,
            'request_id' => $requestId,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function parseHeaders(string $raw): array
    {
        $out = [];
        foreach (preg_split('/\r\n|\n|\r/', $raw) ?: [] as $line) {
            if (!str_contains($line, ':')) {
                continue;
            }
            [$k, $v] = explode(':', $line, 2);
            $out[strtolower(trim($k))] = trim($v);
        }

        return $out;
    }
}

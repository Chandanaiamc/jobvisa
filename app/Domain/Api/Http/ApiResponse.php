<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Api\Http;

use JobVisa\App\Domain\Observability\Services\RequestContext;

/**
 * Standard JSON API envelope helpers.
 */
final class ApiResponse
{
    /**
     * @param  array<string, mixed>|list<mixed>|null  $data
     * @param  array<string, mixed>  $meta
     */
    public static function success(mixed $data = null, array $meta = [], int $status = 200): void
    {
        self::send([
            'success' => true,
            'data' => $data ?? new \stdClass(),
            'meta' => $meta === [] ? new \stdClass() : $meta,
            'request_id' => RequestContext::currentId() ?? '',
        ], $status);
    }

    /**
     * @param  array<string, mixed>  $details
     */
    public static function error(
        string $code,
        string $message,
        int $status = 400,
        array $details = [],
    ): void {
        self::send([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details === [] ? new \stdClass() : $details,
            ],
            'request_id' => RequestContext::currentId() ?? '',
        ], $status);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function send(array $payload, int $status = 200): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function isApiRequest(): bool
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        $path = (string) (parse_url($uri, PHP_URL_PATH) ?: '');

        return str_contains($path, '/api/') || str_ends_with($path, '/api');
    }
}

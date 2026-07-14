<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use JobVisa\App\Domain\Api\Support\ApiVersion;

/**
 * OpenAPI document endpoint.
 *
 * Default: raw OpenAPI JSON (tooling-friendly).
 * Optional: ?envelope=1 wraps in the success envelope for portal-style clients.
 */
final class DocsController extends ApiController
{
    public function openapi(): void
    {
        if (!(bool) config('api.docs_enabled', true) && !in_array(strtolower((string) config('app.env', 'local')), ['local', 'development', 'testing'], true)) {
            $this->fail('docs_disabled', 'API docs disabled.', 404);

            return;
        }

        $path = base_path('docs/05-api/openapi.json');
        if (!is_file($path)) {
            $this->fail('docs_missing', 'OpenAPI document not found.', 404);

            return;
        }
        $json = file_get_contents($path);
        $data = is_string($json) ? json_decode($json, true) : null;
        if (!is_array($data)) {
            $this->fail('docs_invalid', 'OpenAPI document is invalid.', 500);

            return;
        }

        $wantEnvelope = isset($_GET['envelope']) && (string) $_GET['envelope'] !== '0' && (string) $_GET['envelope'] !== '';
        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
        if (!$wantEnvelope && (str_contains($accept, 'application/vnd.oai.openapi') || isset($_GET['raw']))) {
            $wantEnvelope = false;
        }

        if ($wantEnvelope) {
            $this->ok($data, [
                'api_version' => ApiVersion::V1,
                'format' => 'openapi-3.0',
            ]);

            return;
        }

        if (!headers_sent()) {
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

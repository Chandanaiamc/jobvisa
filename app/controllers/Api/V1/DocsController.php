<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use JobVisa\App\Domain\Api\Support\ApiVersion;

/**
 * Development-only OpenAPI docs page / JSON.
 */
final class DocsController extends ApiController
{
    public function openapi(): void
    {
        if (!(bool) config('api.docs_enabled', true) && !in_array(strtolower((string) config('app.env', 'local')), ['local', 'development'], true)) {
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
        $this->ok($data, [
            'api_version' => ApiVersion::V1,
            'format' => 'openapi-3.0',
        ]);
    }
}

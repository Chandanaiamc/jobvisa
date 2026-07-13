<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Core\Controller;
use JobVisa\App\Domain\Api\Auth\ApiAuth;
use JobVisa\App\Domain\Api\Http\ApiException;
use JobVisa\App\Domain\Api\Http\ApiRequestValidator;
use JobVisa\App\Domain\Api\Http\ApiResponse;
use JobVisa\App\Domain\Api\Resources\ApiResource;
use JobVisa\App\Domain\Api\Support\ApiVersion;
use JobVisa\App\Domain\Observability\Services\RequestContext;

/**
 * Base API v1 controller.
 */
abstract class ApiController extends Controller
{
    protected function ok(mixed $data = null, array $meta = [], int $status = 200): void
    {
        ApiResponse::success($data, $meta, $status);
    }

    protected function fail(string $code, string $message, int $status = 400, array $details = []): void
    {
        ApiResponse::error($code, $message, $status, $details);
    }

    protected function validator(): ApiRequestValidator
    {
        return container(ApiRequestValidator::class);
    }

    /**
     * @return array<string, mixed>
     */
    protected function actor(): array
    {
        if (!ApiAuth::check()) {
            throw ApiException::unauthorized();
        }

        return ApiAuth::actor();
    }

    /**
     * @return array<string, mixed>
     */
    protected function platformMeta(): array
    {
        return [
            'api_version' => ApiVersion::V1,
            'platform_version' => ApiVersion::CURRENT,
            'request_id' => RequestContext::currentId() ?? '',
        ];
    }
}

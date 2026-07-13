<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use JobVisa\App\Domain\Api\Support\ApiVersion;
use JobVisa\App\Domain\Production\Services\ProductionHealthService;

final class HealthController extends ApiController
{
    public function index(): void
    {
        $live = container(ProductionHealthService::class)->live();
        $this->ok([
            'status' => 'ok',
            'api_version' => ApiVersion::V1,
            'platform_version' => ApiVersion::CURRENT,
            'app' => $live['app'] ?? config('app.name'),
            'time' => gmdate('c'),
        ], $this->platformMeta());
    }
}

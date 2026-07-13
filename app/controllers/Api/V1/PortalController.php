<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use JobVisa\App\Domain\Api\Portal\Services\DeveloperPortalService;

/**
 * Public portal readiness probe for SDK / ops.
 */
final class PortalController extends ApiController
{
    public function index(): void
    {
        $status = container(DeveloperPortalService::class)->status();
        $this->ok($status, $this->platformMeta(), ($status['status'] ?? '') === 'ok' ? 200 : 503);
    }
}

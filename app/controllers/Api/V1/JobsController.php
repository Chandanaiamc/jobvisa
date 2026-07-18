<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use JobVisa\App\Domain\Api\Http\ApiException;
use JobVisa\App\Domain\Job\Services\PublicJobsService;

final class JobsController extends ApiController
{
    public function index(): void
    {
        $input = $this->validator()->validate($_GET, [
            'q' => 'max:120',
            'country_id' => 'integer|min:1',
            'job_type_id' => 'integer|min:1',
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
            'limit' => 'integer|min:1|max:100',
        ]);

        /** @var PublicJobsService $svc */
        $svc = container(PublicJobsService::class);
        $result = $svc->search($input);

        $meta = array_merge($this->platformMeta(), [
            'count' => count($result['jobs']),
            'pagination' => $result['pagination'],
            'filters_applied' => $result['filters_applied'],
        ]);

        $includeRaw = strtolower((string) ($_GET['include_filters'] ?? ''));
        if (in_array($includeRaw, ['1', 'true', 'yes'], true)) {
            $meta['filter_options'] = $svc->filterOptions();
        }

        $this->ok(['jobs' => $result['jobs']], $meta);
    }

    public function show(string $job): void
    {
        $jobId = (int) $job;
        if ($jobId < 1) {
            throw ApiException::validation('Invalid job id.', ['job' => ['Must be a positive integer.']]);
        }

        /** @var PublicJobsService $svc */
        $svc = container(PublicJobsService::class);
        $row = $svc->find($jobId);
        if ($row === null) {
            throw ApiException::notFound('Job not found.');
        }

        $this->ok(['job' => $row], $this->platformMeta());
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use JobVisa\App\Domain\Api\Http\ApiException;
use JobVisa\App\Domain\Api\Resources\ApiResource;
use JobVisa\App\Repositories\Contracts\JobRepositoryInterface;

final class JobsController extends ApiController
{
    public function index(): void
    {
        $input = $this->validator()->validate($_GET, [
            'limit' => 'integer|min:1|max:100',
        ]);
        $limit = (int) ($input['limit'] ?? 50);
        $jobs = container(JobRepositoryInterface::class)->findPublished($limit);
        $data = array_map(static fn (array $j): array => ApiResource::jobPublic($j), $jobs);
        $this->ok(['jobs' => $data], array_merge($this->platformMeta(), ['count' => count($data)]));
    }

    public function show(string $job): void
    {
        $jobId = (int) $job;
        if ($jobId < 1) {
            throw ApiException::validation('Invalid job id.', ['job' => ['Must be a positive integer.']]);
        }
        $row = container(JobRepositoryInterface::class)->findPublishedRecordById($jobId);
        if ($row === null) {
            throw ApiException::notFound('Job not found.');
        }
        $this->ok(['job' => ApiResource::jobPublic($row)], $this->platformMeta());
    }
}

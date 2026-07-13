<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use JobVisa\App\Domain\Api\Http\ApiException;
use JobVisa\App\Domain\JobMatching\Services\JobMatchService;

final class JobMatchController extends ApiController
{
    public function show(string $job): void
    {
        $jobId = (int) $job;
        $input = $this->validator()->validate($_GET, [
            'resume_id' => 'required|integer|min:1',
        ]);
        $resumeId = (int) $input['resume_id'];
        $actor = $this->actor();
        try {
            $page = container(JobMatchService::class)->matchPage($actor, $resumeId, $jobId, false);
        } catch (\Throwable) {
            throw ApiException::notFound('Match unavailable for this job/resume.');
        }

        $match = $page['match'] ?? null;
        $safeMatch = null;
        if (is_object($match) && method_exists($match, 'toArray')) {
            $safeMatch = $match->toArray();
        } elseif (is_array($match)) {
            $safeMatch = $match;
        }

        $this->ok([
            'resume' => $page['resume'] ?? null,
            'job' => $page['job'] ?? null,
            'match' => $safeMatch,
            'disclaimer' => $page['disclaimer'] ?? '',
        ], $this->platformMeta());
    }
}

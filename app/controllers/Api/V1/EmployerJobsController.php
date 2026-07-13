<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use JobVisa\App\Domain\Api\Http\ApiException;
use JobVisa\App\Domain\Api\Resources\ApiResource;
use JobVisa\App\Domain\ApplicantRanking\Services\ApplicantRankingService;
use JobVisa\App\Repositories\Contracts\ApplicationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\JobRepositoryInterface;

final class EmployerJobsController extends ApiController
{
    public function index(): void
    {
        $actor = $this->actor();
        $userId = (int) ($actor['id'] ?? 0);
        $jobs = container(JobRepositoryInterface::class)->listOwnedByEmployerUser($userId, 50);
        $data = array_map(static fn (array $j): array => ApiResource::jobEmployer($j), $jobs);
        $this->ok(['jobs' => $data], array_merge($this->platformMeta(), ['count' => count($data)]));
    }

    public function applicants(string $job): void
    {
        $jobId = (int) $job;
        $actor = $this->actor();
        $userId = (int) ($actor['id'] ?? 0);
        $owned = container(JobRepositoryInterface::class)->findOwnedByEmployerUser($jobId, $userId);
        if ($owned === null) {
            throw ApiException::notFound('Job not found.');
        }
        $apps = container(ApplicationRepositoryInterface::class)->findDetailedByJobId($jobId, 200);
        $data = array_map(static fn (array $a): array => ApiResource::applicant($a), $apps);
        $this->ok([
            'job' => ApiResource::jobEmployer($owned),
            'applicants' => $data,
        ], array_merge($this->platformMeta(), ['count' => count($data)]));
    }

    public function ranking(string $job): void
    {
        $jobId = (int) $job;
        $actor = $this->actor();
        try {
            $page = container(ApplicantRankingService::class)->rankingPage($actor, $jobId, [], false);
        } catch (\Throwable) {
            throw ApiException::notFound('Ranking unavailable for this job.');
        }

        $candidates = [];
        foreach ($page['candidates'] ?? [] as $c) {
            if (is_object($c) && method_exists($c, 'toArray')) {
                $row = $c->toArray();
            } elseif (is_array($c)) {
                $row = $c;
            } else {
                continue;
            }
            unset($row['email'], $row['phone'], $row['raw']);
            $candidates[] = $row;
        }

        $this->ok([
            'job' => $page['job'] ?? null,
            'candidates' => $candidates,
            'total_ranked' => $page['total_ranked'] ?? count($candidates),
            'disclaimer' => $page['disclaimer'] ?? 'Heuristic ranking only.',
        ], $this->platformMeta());
    }
}

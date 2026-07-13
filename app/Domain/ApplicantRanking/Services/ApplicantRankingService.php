<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\ApplicantRanking\Services;

use JobVisa\App\Domain\ApplicantRanking\DTO\RankedApplicantDTO;
use JobVisa\App\Domain\ApplicantRanking\DTO\RankingFilterDTO;
use JobVisa\App\Domain\ApplicantRanking\Exceptions\ApplicantRankingException;
use JobVisa\App\Domain\ApplicantRanking\Policies\ApplicantRankingPolicy;
use JobVisa\App\Domain\ApplicantRanking\Validators\ApplicantRankingValidator;
use JobVisa\App\Repositories\Contracts\ApplicationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\JobApplicantRankingRepositoryInterface;
use JobVisa\App\Repositories\Contracts\JobRepositoryInterface;

/**
 * Employer-facing applicant ranking application service.
 */
final class ApplicantRankingService
{
    public function __construct(
        private readonly JobRepositoryInterface $jobs,
        private readonly ApplicationRepositoryInterface $applications,
        private readonly JobApplicantRankingRepositoryInterface $rankings,
        private readonly ApplicantRankingScoringService $scoring,
        private readonly ApplicantRankingPolicy $policy,
        private readonly ApplicantRankingValidator $validator,
    ) {
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    public function jobsIndex(array $actor): array
    {
        $userId = (int) ($actor['id'] ?? 0);
        $jobs = $this->jobs->listOwnedByEmployerUser($userId, 50);

        return [
            'jobs' => $jobs,
            'disclaimer' => 'Select a published job to view ranked applicants.',
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $filterInput
     * @return array<string, mixed>
     */
    public function rankingPage(array $actor, int $jobId, array $filterInput = [], bool $force = false): array
    {
        $this->validator->assertJobId($jobId);
        $filters = $this->validator->filters($filterInput);
        $job = $this->requireOwnedJob($actor, $jobId);

        $existing = $this->rankings->listByJobId($jobId, 200);
        if ($force || $existing === []) {
            $ranked = $this->recalculateInternal($jobId, true);
        } else {
            $ranked = array_map(
                static fn (array $row): RankedApplicantDTO => RankedApplicantDTO::fromRow($row),
                $existing
            );
        }

        $filtered = $this->applyFilters($ranked, $filters);
        $top = array_slice($filtered, 0, $filters->top);

        return [
            'job' => [
                'id' => (int) $job['id'],
                'title' => (string) ($job['title'] ?? ''),
                'status' => (string) ($job['status'] ?? ''),
                'country_name' => (string) ($job['country_name'] ?? ''),
            ],
            'filters' => $filters,
            'candidates' => $top,
            'total_ranked' => count($ranked),
            'total_filtered' => count($filtered),
            'can_recalculate' => $this->policy->canRecalculate($actor, $job),
            'disclaimer' => 'Rankings are deterministic heuristics using resume intelligence, job match, skills, experience, education, certifications, portfolio and references. Not a hiring decision.',
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, count: int}
     */
    public function recalculate(array $actor, int $jobId): array
    {
        $this->validator->assertJobId($jobId);
        $job = $this->requireOwnedJob($actor, $jobId);
        if (!$this->policy->canRecalculate($actor, $job)) {
            throw ApplicantRankingException::forbidden();
        }

        $ranked = $this->recalculateInternal($jobId, true);

        return [
            'success' => true,
            'message' => sprintf('Ranked %d applicant(s).', count($ranked)),
            'count' => count($ranked),
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    public function historyPage(array $actor, int $jobId): array
    {
        $this->validator->assertJobId($jobId);
        $job = $this->requireOwnedJob($actor, $jobId);
        $history = $this->rankings->listHistoryByJobId($jobId, 100);

        return [
            'job' => [
                'id' => (int) $job['id'],
                'title' => (string) ($job['title'] ?? ''),
            ],
            'history' => $history,
            'can_manage' => $this->policy->canManageHistory($actor, $job),
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function softDeleteHistoryEntry(array $actor, int $jobId, int $historyId): array
    {
        $job = $this->requireOwnedJob($actor, $jobId);
        if (!$this->policy->canManageHistory($actor, $job)) {
            throw ApplicantRankingException::forbidden();
        }
        $ok = $this->rankings->softDeleteHistory($historyId, $jobId);

        return [
            'success' => $ok,
            'message' => $ok ? 'History entry removed.' : 'History entry not found.',
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function clearHistory(array $actor, int $jobId): array
    {
        $job = $this->requireOwnedJob($actor, $jobId);
        if (!$this->policy->canManageHistory($actor, $job)) {
            throw ApplicantRankingException::forbidden();
        }
        $n = $this->rankings->softDeleteAllHistoryForJob($jobId);

        return [
            'success' => true,
            'message' => $n > 0 ? sprintf('Cleared %d history entries.', $n) : 'No history to clear.',
        ];
    }

    /**
     * @return list<RankedApplicantDTO>
     */
    private function recalculateInternal(int $jobId, bool $persist): array
    {
        $apps = $this->applications->findDetailedByJobId($jobId, 200);
        $scored = [];
        foreach ($apps as $app) {
            // Skip withdrawn from active ranking board? Still score but can filter.
            $scored[] = $this->scoring->scoreApplicant($app, $jobId, true);
        }

        usort(
            $scored,
            static function (RankedApplicantDTO $a, RankedApplicantDTO $b): int {
                if ($a->overallScore === $b->overallScore) {
                    return $b->jobMatchScore <=> $a->jobMatchScore;
                }

                return $b->overallScore <=> $a->overallScore;
            }
        );

        $ranked = [];
        $position = 1;
        foreach ($scored as $dto) {
            $ranked[] = $dto->withRank($position);
            $position++;
        }

        if ($persist) {
            $this->rankings->softDeleteAllForJob($jobId);
            foreach ($ranked as $dto) {
                $payload = $dto->toPersistPayload();
                $this->rankings->upsert($jobId, $dto->applicationId, $payload);
                $this->rankings->appendHistory($jobId, $dto->applicationId, $payload);
            }
        }

        return $ranked;
    }

    /**
     * @param  list<RankedApplicantDTO>  $ranked
     * @return list<RankedApplicantDTO>
     */
    private function applyFilters(array $ranked, RankingFilterDTO $filters): array
    {
        $out = [];
        foreach ($ranked as $dto) {
            if ($filters->statuses !== [] && !in_array($dto->applicationStatus, $filters->statuses, true)) {
                continue;
            }
            if ($filters->minOverall !== null && $dto->overallScore < $filters->minOverall) {
                continue;
            }
            if ($filters->q !== '') {
                $hay = mb_strtolower($dto->applicantName . ' ' . $dto->applicantEmail);
                if (!str_contains($hay, mb_strtolower($filters->q))) {
                    continue;
                }
            }
            $out[] = $dto;
        }

        $dir = $filters->direction === 'asc' ? 1 : -1;
        usort($out, static function (RankedApplicantDTO $a, RankedApplicantDTO $b) use ($filters, $dir): int {
            $cmp = match ($filters->sort) {
                RankingFilterDTO::SORT_MATCH => $a->jobMatchScore <=> $b->jobMatchScore,
                RankingFilterDTO::SORT_RESUME => $a->resumeScore <=> $b->resumeScore,
                RankingFilterDTO::SORT_APPLIED => strcmp($a->appliedAt, $b->appliedAt),
                RankingFilterDTO::SORT_OVERALL => $a->overallScore <=> $b->overallScore,
                default => $a->rankPosition <=> $b->rankPosition,
            };
            // rank default is ascending position; for score sorts multiply by dir
            if ($filters->sort === RankingFilterDTO::SORT_RANK) {
                return $cmp;
            }

            return $cmp * $dir;
        });

        return $out;
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    private function requireOwnedJob(array $actor, int $jobId): array
    {
        $userId = (int) ($actor['id'] ?? 0);
        $job = $this->jobs->findOwnedByEmployerUser($jobId, $userId);
        if ($job === null) {
            // Distinguish not found vs forbidden carefully: if job exists but not owned → forbidden
            $any = $this->jobs->findRecordById($jobId);
            if ($any === null) {
                throw ApplicantRankingException::jobNotFound();
            }
            throw ApplicantRankingException::forbidden();
        }

        if (!$this->policy->canView($actor, $job)) {
            throw ApplicantRankingException::forbidden();
        }

        return $job;
    }
}

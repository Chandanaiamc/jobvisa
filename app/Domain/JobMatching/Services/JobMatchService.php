<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\JobMatching\Services;

use JobVisa\App\Domain\JobMatching\DTO\JobMatchResultDTO;
use JobVisa\App\Domain\JobMatching\Exceptions\JobMatchException;
use JobVisa\App\Domain\JobMatching\Policies\JobMatchPolicy;
use JobVisa\App\Domain\JobMatching\Validators\JobMatchValidator;
use JobVisa\App\Domain\Resume\Aggregates\ResumeAggregate;
use JobVisa\App\Domain\Resume\Repositories\ResumeRepositoryInterface;
use JobVisa\App\Domain\Resume\Support\ResumeCompletionCalculator;
use JobVisa\App\Repositories\Contracts\JobRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeJobMatchRepositoryInterface;

/**
 * Application service for resume↔job matching (does not alter completion %).
 */
final class JobMatchService
{
    public function __construct(
        private readonly ResumeRepositoryInterface $resumes,
        private readonly JobRepositoryInterface $jobs,
        private readonly ResumeJobMatchRepositoryInterface $snapshots,
        private readonly JobMatchContextFactory $contexts,
        private readonly JobMatchScoringService $scoring,
        private readonly JobMatchPolicy $policy,
        private readonly JobMatchValidator $validator,
        private readonly ResumeCompletionCalculator $completion,
    ) {
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    public function matchPage(array $actor, int $resumeId, int $jobId, bool $force = false): array
    {
        $this->validator->assertIds($resumeId, $jobId);
        $aggregate = $this->requireResume($actor, $resumeId);
        $job = $this->jobs->findPublishedRecordById($jobId);
        if (!$this->policy->canViewMatch($actor, $aggregate->resume(), $job)) {
            if ($job === null || !$this->policy->isEligibleJob($job)) {
                throw JobMatchException::jobNotFound();
            }
            throw JobMatchException::forbidden();
        }

        $userId = $aggregate->resume()->userId();
        $canEdit = $this->policy->canRecalculate($actor, $aggregate->resume(), $job);

        $match = null;
        if (!$force) {
            $row = $this->snapshots->findByResumeAndJob($resumeId, $jobId);
            if ($row !== null) {
                $match = JobMatchResultDTO::fromSnapshotRow($row, (string) ($job['title'] ?? ''));
            }
        }

        if ($match === null) {
            $match = $this->computeAndPersist($resumeId, $userId, $jobId, $canEdit);
        }

        $completion = $this->completion->evaluate($userId, $resumeId);

        return [
            'resume' => [
                'id' => $resumeId,
                'title' => $aggregate->resume()->title(),
                'status' => $aggregate->resume()->status(),
                'completeness_score' => $completion['score'],
            ],
            'job' => [
                'id' => (int) ($job['id'] ?? $jobId),
                'title' => (string) ($job['title'] ?? ''),
                'slug' => (string) ($job['slug'] ?? ''),
                'country_name' => (string) ($job['country_name'] ?? ''),
                'job_type_name' => (string) ($job['job_type_name'] ?? ''),
                'experience_min_years' => $job['experience_min_years'] ?? null,
            ],
            'completion' => $completion,
            'match' => $match,
            'can_edit' => $canEdit,
            'disclaimer' => 'Match scores are deterministic heuristics based on your resume and the published job posting. They are guidance only — not hiring decisions.',
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, match?: JobMatchResultDTO}
     */
    public function recalculate(array $actor, int $resumeId, int $jobId): array
    {
        $this->validator->assertIds($resumeId, $jobId);
        $aggregate = $this->requireResume($actor, $resumeId);
        $job = $this->jobs->findPublishedRecordById($jobId);
        if (!$this->policy->canRecalculate($actor, $aggregate->resume(), $job)) {
            if ($job === null || !$this->policy->isEligibleJob($job)) {
                throw JobMatchException::jobNotFound();
            }
            throw JobMatchException::forbidden();
        }

        $dto = $this->computeAndPersist($resumeId, $aggregate->resume()->userId(), $jobId, true);

        return [
            'success' => true,
            'message' => 'Job match recalculated.',
            'match' => $dto,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    public function recommendedPage(array $actor, int $resumeId): array
    {
        $this->validator->assertResumeId($resumeId);
        $aggregate = $this->requireResume($actor, $resumeId);
        if (!$this->policy->canViewRecommendations($actor, $aggregate->resume())) {
            throw JobMatchException::forbidden();
        }

        $userId = $aggregate->resume()->userId();
        $canEdit = $this->policy->canManageMatches($actor, $aggregate->resume());

        // Score published jobs missing snapshots (bounded batch)
        $published = $this->jobs->findPublished(30);
        $scored = [];
        foreach ($published as $job) {
            $jobId = (int) ($job['id'] ?? 0);
            if ($jobId < 1) {
                continue;
            }
            $existing = $this->snapshots->findByResumeAndJob($resumeId, $jobId);
            if ($existing === null && $canEdit) {
                $this->computeAndPersist($resumeId, $userId, $jobId, true);
            } elseif ($existing === null) {
                // Viewers: compute in-memory without persist
                $ctx = $this->contexts->build($resumeId, $userId, $jobId);
                $dto = $this->scoring->score($ctx);
                $scored[] = [
                    'job_id' => $jobId,
                    'job_title' => $dto->jobTitle,
                    'overall_score' => $dto->overallScore,
                    'skills_score' => $dto->skillsScore,
                    'experience_score' => $dto->experienceScore,
                    'country_name' => (string) ($job['country_name'] ?? ''),
                    'calculated_at' => $dto->calculatedAt,
                ];
                continue;
            }
        }

        $rows = $this->snapshots->listTopForResume($resumeId, 20);
        if ($scored !== [] && $rows === []) {
            usort($scored, static fn (array $a, array $b): int => $b['overall_score'] <=> $a['overall_score']);
            $rows = $scored;
        }

        $completion = $this->completion->evaluate($userId, $resumeId);

        return [
            'resume' => [
                'id' => $resumeId,
                'title' => $aggregate->resume()->title(),
                'status' => $aggregate->resume()->status(),
                'completeness_score' => $completion['score'],
            ],
            'completion' => $completion,
            'recommendations' => $rows,
            'can_edit' => $canEdit,
            'disclaimer' => 'Recommended jobs are ranked by deterministic match scores against published vacancies.',
        ];
    }

    private function computeAndPersist(int $resumeId, int $userId, int $jobId, bool $persist): JobMatchResultDTO
    {
        $context = $this->contexts->build($resumeId, $userId, $jobId);
        $dto = $this->scoring->score($context);

        if ($persist) {
            $this->snapshots->upsert($resumeId, $jobId, $dto->toPersistPayload());
        }

        return $dto;
    }

    /**
     * @param  array<string, mixed>  $actor
     */
    private function requireResume(array $actor, int $resumeId): ResumeAggregate
    {
        $aggregate = $this->resumes->findAggregateById($resumeId);
        if ($aggregate === null || $aggregate->resume()->deletedAt() !== null) {
            throw JobMatchException::resumeNotFound();
        }

        if (!$this->policy->canViewRecommendations($actor, $aggregate->resume())) {
            throw JobMatchException::forbidden();
        }

        return $aggregate;
    }
}

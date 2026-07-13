<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Intelligence\Services;

use JobVisa\App\Domain\Resume\Aggregates\ResumeAggregate;
use JobVisa\App\Domain\Resume\Exceptions\ResumeException;
use JobVisa\App\Domain\Resume\Intelligence\DTO\RecommendationDTO;
use JobVisa\App\Domain\Resume\Intelligence\DTO\ResumeIntelligenceDTO;
use JobVisa\App\Domain\Resume\Intelligence\Policies\ResumeIntelligencePolicy;
use JobVisa\App\Domain\Resume\Repositories\ResumeRepositoryInterface;
use JobVisa\App\Domain\Resume\Support\ResumeCompletionCalculator;
use JobVisa\App\Repositories\Contracts\ResumeIntelligenceHistoryRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeIntelligenceRepositoryInterface;

/**
 * Application service for resume intelligence (separate from completion %).
 * Sprint 2F.2: keyword matching, skill gaps, score history.
 */
final class ResumeIntelligenceService
{
    public function __construct(
        private readonly ResumeRepositoryInterface $resumes,
        private readonly ResumeIntelligenceRepositoryInterface $snapshots,
        private readonly ResumeIntelligenceHistoryRepositoryInterface $history,
        private readonly ResumeIntelligenceContextFactory $contexts,
        private readonly ResumeIntelligenceCalculator $calculator,
        private readonly ResumeIntelligencePolicy $policy,
        private readonly ResumeCompletionCalculator $completion,
    ) {
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    public function page(array $actor, int $resumeId, bool $forceRecalculate = false): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, true);
        $canEdit = $this->policy->canRecalculate($actor, $aggregate->resume());
        $userId = $aggregate->resume()->userId();

        $intelligence = null;
        if (!$forceRecalculate) {
            $row = $this->snapshots->findLatestByResumeId($resumeId);
            if ($row !== null) {
                $intelligence = ResumeIntelligenceDTO::fromRow($row, $canEdit);
            }
        }

        if ($intelligence === null) {
            if (!$canEdit && !$forceRecalculate) {
                $intelligence = $this->computeAndMaybePersist($resumeId, $userId, $canEdit, false, null);
            } else {
                $intelligence = $this->computeAndMaybePersist($resumeId, $userId, $canEdit, $canEdit, null);
            }
        }

        $completion = $this->completion->evaluate($userId, $resumeId);
        $historyRows = $canEdit ? $this->history->listByResumeId($resumeId, 25) : [];

        return [
            'resume' => [
                'id' => $resumeId,
                'title' => $aggregate->resume()->title(),
                'status' => $aggregate->resume()->status(),
                'completeness_score' => $completion['score'],
            ],
            'completion' => $completion,
            'intelligence' => $intelligence,
            'history' => $historyRows,
            'can_edit' => $canEdit,
            'disclaimer' => 'Scores are explainable heuristics to guide improvements. They are not ATS approval guarantees and do not evaluate protected personal characteristics.',
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, intelligence?: ResumeIntelligenceDTO}
     */
    public function recalculate(array $actor, int $resumeId, ?string $targetRole = null): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);
        $userId = $aggregate->resume()->userId();
        $role = $targetRole !== null ? trim($targetRole) : null;
        if ($role === '') {
            $role = null;
        }
        $dto = $this->computeAndMaybePersist($resumeId, $userId, true, true, $role);

        return [
            'success' => true,
            'message' => 'Resume intelligence recalculated.',
            'intelligence' => $dto,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function softDeleteHistoryEntry(array $actor, int $resumeId, int $historyId): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);
        if (!$this->policy->canManageHistory($actor, $aggregate->resume())) {
            throw ResumeException::forbidden();
        }

        $ok = $this->history->softDelete($historyId, $resumeId);

        return [
            'success' => $ok,
            'message' => $ok ? 'Score history entry removed.' : 'History entry not found.',
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function clearHistory(array $actor, int $resumeId): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);
        if (!$this->policy->canManageHistory($actor, $aggregate->resume())) {
            throw ResumeException::forbidden();
        }

        $n = $this->history->softDeleteAllForResume($resumeId);

        return [
            'success' => true,
            'message' => $n > 0
                ? sprintf('Cleared %d score history entries.', $n)
                : 'No score history to clear.',
        ];
    }

    private function computeAndMaybePersist(
        int $resumeId,
        int $userId,
        bool $canEdit,
        bool $persist,
        ?string $targetRole,
    ): ResumeIntelligenceDTO {
        $context = $this->contexts->build($resumeId, $userId);
        $dto = $this->calculator->calculate($context, $canEdit, $targetRole);

        if ($persist) {
            $payload = [
                'overall_score' => $dto->overallScore,
                'ats_score' => $dto->atsScore,
                'employer_readiness_score' => $dto->employerReadinessScore,
                'keyword_match_score' => $dto->keywordMatchScore,
                'strength_level' => $dto->strengthLevel,
                'score_breakdown' => $dto->breakdown,
                'recommendations' => array_map(
                    static fn (RecommendationDTO $r): array => $r->toArray(),
                    $dto->recommendations
                ),
                'analysis_json' => $dto->analysis,
                'target_role' => $dto->targetRole,
                'rules_version' => $dto->rulesVersion,
                'calculated_at' => $dto->calculatedAt,
            ];
            $this->snapshots->upsert($resumeId, $payload);
            $this->history->append($resumeId, $payload);
        }

        return $dto;
    }

    /**
     * @param  array<string, mixed>  $actor
     */
    private function requireResume(array $actor, int $resumeId, bool $viewOnly): ResumeAggregate
    {
        $aggregate = $this->resumes->findAggregateById($resumeId);
        if ($aggregate === null || $aggregate->resume()->deletedAt() !== null) {
            throw ResumeException::notFound();
        }

        $allowed = $viewOnly
            ? $this->policy->canView($actor, $aggregate->resume())
            : $this->policy->canRecalculate($actor, $aggregate->resume());

        if (!$allowed) {
            throw ResumeException::forbidden();
        }

        return $aggregate;
    }
}

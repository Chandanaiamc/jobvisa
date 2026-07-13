<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\LearningPath\Services;

use JobVisa\App\Domain\LearningPath\DTO\LearningPathDTO;
use JobVisa\App\Domain\LearningPath\Exceptions\LearningPathException;
use JobVisa\App\Domain\LearningPath\Policies\LearningPathPolicy;
use JobVisa\App\Domain\LearningPath\Support\LearningPathVersion;
use JobVisa\App\Domain\LearningPath\Validators\LearningPathValidator;
use JobVisa\App\Domain\Resume\Repositories\ResumeRepositoryInterface;
use JobVisa\App\Domain\Resume\Support\ResumeCompletionCalculator;
use JobVisa\App\Repositories\Contracts\CareerCoachSessionRepositoryInterface;
use JobVisa\App\Repositories\Contracts\LearningPathHistoryRepositoryInterface;
use JobVisa\App\Repositories\Contracts\LearningPathRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeIntelligenceRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeProfessionalRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeSkillRepositoryInterface;
use JobVisa\App\Repositories\Contracts\SalaryIntelligencePredictionRepositoryInterface;
use JobVisa\App\Repositories\Contracts\SkillGapAnalysisRepositoryInterface;

/**
 * AI Learning Path Generator application service.
 */
final class LearningPathService
{
    public function __construct(
        private readonly ResumeRepositoryInterface $resumes,
        private readonly LearningPathRepositoryInterface $paths,
        private readonly LearningPathHistoryRepositoryInterface $history,
        private readonly LearningPathAnalyzer $analyzer,
        private readonly LearningPathPdfExporter $pdfExporter,
        private readonly SkillGapAnalysisRepositoryInterface $skillGaps,
        private readonly SalaryIntelligencePredictionRepositoryInterface $salary,
        private readonly CareerCoachSessionRepositoryInterface $coach,
        private readonly ResumeIntelligenceRepositoryInterface $intelligence,
        private readonly ResumeProfessionalRepositoryInterface $professional,
        private readonly ResumeSkillRepositoryInterface $skills,
        private readonly ResumeCompletionCalculator $completion,
        private readonly LearningPathPolicy $policy,
        private readonly LearningPathValidator $validator,
    ) {
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    public function page(array $actor, int $resumeId): array
    {
        $this->validator->assertResumeId($resumeId);
        $aggregate = $this->requireOwned($actor, $resumeId, 'view');
        $resume = $aggregate->resume();
        $userId = (int) $actor['id'];

        $row = $this->paths->findLatestByResumeId($resumeId, $userId);
        $path = $row !== null ? LearningPathDTO::fromRow($row) : null;

        $pro = $this->professional->findByResumeId($resumeId) ?? [];
        $coach = $this->coach->findByResumeId($resumeId);
        $defaultGoal = trim((string) ($coach['target_role'] ?? $pro['current_job_title'] ?? $pro['headline'] ?? ''));

        return [
            'resume' => [
                'id' => $resume->id(),
                'title' => $resume->title(),
            ],
            'completion' => $this->completion->evaluate($userId, $resumeId),
            'path' => $path,
            'default_career_goal' => $defaultGoal,
            'versions' => $this->paths->listByResumeId($resumeId, $userId, 12),
            'history' => $this->history->listByResumeId($resumeId, 20),
            'deleted_history' => $this->history->listDeletedByResumeId($resumeId, 10),
            'can_edit' => $this->policy->canGenerate($actor, $resume),
            'version' => LearningPathVersion::CURRENT,
            'disclaimer' => 'Learning paths are generated from your resume, skill-gap, salary and career-coach signals using deterministic rules. Recommendations are guidance only — not accredited endorsements.',
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    public function historyPage(array $actor, int $resumeId): array
    {
        $this->validator->assertResumeId($resumeId);
        $aggregate = $this->requireOwned($actor, $resumeId, 'view');
        $resume = $aggregate->resume();

        return [
            'resume' => [
                'id' => $resume->id(),
                'title' => $resume->title(),
            ],
            'history' => $this->history->listByResumeId($resumeId, 50),
            'deleted_history' => $this->history->listDeletedByResumeId($resumeId, 20),
            'versions' => $this->paths->listByResumeId($resumeId, (int) $actor['id'], 30),
            'version' => LearningPathVersion::CURRENT,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, path_id: int}
     */
    public function generate(array $actor, int $resumeId, ?string $careerGoal = null): array
    {
        return $this->runGenerate($actor, $resumeId, $careerGoal, 'generate');
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, path_id: int}
     */
    public function recalculate(array $actor, int $resumeId, ?string $careerGoal = null): array
    {
        return $this->runGenerate($actor, $resumeId, $careerGoal, 'recalculate');
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function toggleMilestone(array $actor, int $resumeId, int $pathId, string $milestoneKey, bool $done = true): array
    {
        $this->validator->assertResumeId($resumeId);
        $this->validator->assertPathId($pathId);
        $key = $this->validator->normalizeMilestoneKey($milestoneKey);
        $this->requireOwned($actor, $resumeId, 'generate');
        $userId = (int) $actor['id'];

        $row = $this->paths->findOwned($pathId, $resumeId, $userId);
        if ($row === null) {
            throw LearningPathException::pathNotFound();
        }
        $dto = LearningPathDTO::fromRow($row);
        $result = $this->analyzer->applyMilestone($dto->path, $key, $done);
        if (empty($result['found'])) {
            throw LearningPathException::milestoneNotFound();
        }

        $ok = $this->paths->updateProgress($pathId, $resumeId, $userId, [
            'path_json' => $result['path_json'],
            'progress_percent' => $result['progress_percent'],
            'milestones_total' => $result['milestones_total'],
            'milestones_done' => $result['milestones_done'],
        ]);

        $this->history->append($resumeId, $userId, [
            'path_id' => $pathId,
            'action' => $done ? 'milestone_complete' : 'milestone_reopen',
            'headline' => sprintf(
                'Milestone %s · Progress %d%%',
                $key,
                (int) $result['progress_percent']
            ),
            'progress_percent' => (int) $result['progress_percent'],
            'timeline_weeks' => $dto->timelineWeeks,
            'rules_version' => LearningPathVersion::CURRENT,
            'snapshot_json' => [
                'milestone_key' => $key,
                'done' => $done,
                'progress_percent' => $result['progress_percent'],
            ],
        ]);

        return [
            'success' => $ok,
            'message' => $ok
                ? sprintf('Progress updated to %d%%.', (int) $result['progress_percent'])
                : 'Unable to update milestone.',
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function softDeleteHistory(array $actor, int $resumeId, int $historyId): array
    {
        $this->validator->assertResumeId($resumeId);
        $this->validator->assertHistoryId($historyId);
        $this->requireOwned($actor, $resumeId, 'history');
        $ok = $this->history->softDelete($historyId, $resumeId);

        return [
            'success' => $ok,
            'message' => $ok ? 'History entry removed.' : 'History entry not found.',
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function restoreHistory(array $actor, int $resumeId, int $historyId): array
    {
        $this->validator->assertResumeId($resumeId);
        $this->validator->assertHistoryId($historyId);
        $this->requireOwned($actor, $resumeId, 'history');
        $ok = $this->history->restore($historyId, $resumeId);

        return [
            'success' => $ok,
            'message' => $ok ? 'History entry restored.' : 'Deleted history entry not found.',
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function purgeHistory(array $actor, int $resumeId, int $historyId): array
    {
        $this->validator->assertResumeId($resumeId);
        $this->validator->assertHistoryId($historyId);
        $this->requireOwned($actor, $resumeId, 'history');
        $ok = $this->history->permanentDelete($historyId, $resumeId);

        return [
            'success' => $ok,
            'message' => $ok ? 'History entry permanently deleted.' : 'History entry not found.',
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function clearHistory(array $actor, int $resumeId): array
    {
        $this->validator->assertResumeId($resumeId);
        $this->requireOwned($actor, $resumeId, 'history');
        $n = $this->history->softDeleteAllForResume($resumeId);

        return [
            'success' => true,
            'message' => $n > 0
                ? sprintf('Cleared %d history entries.', $n)
                : 'No history to clear.',
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{filename: string, mime: string, content: string}
     */
    public function exportPdf(array $actor, int $resumeId, int $pathId): array
    {
        $this->validator->assertResumeId($resumeId);
        $this->validator->assertPathId($pathId);
        $this->requireOwned($actor, $resumeId, 'export');
        $row = $this->paths->findOwned($pathId, $resumeId, (int) $actor['id']);
        if ($row === null) {
            throw LearningPathException::pathNotFound();
        }
        $dto = LearningPathDTO::fromRow($row);
        $content = $this->pdfExporter->export($dto);

        return [
            'filename' => 'learning-path-' . $pathId . '.pdf',
            'mime' => 'application/pdf',
            'content' => $content,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, path_id: int}
     */
    private function runGenerate(array $actor, int $resumeId, ?string $careerGoal, string $action): array
    {
        $this->validator->assertResumeId($resumeId);
        $this->requireOwned($actor, $resumeId, 'generate');
        $userId = (int) $actor['id'];
        $goal = $this->validator->normalizeCareerGoal($careerGoal);
        $context = $this->buildContext($resumeId, $userId, $goal);
        $dto = $this->analyzer->generate($resumeId, $userId, $context);
        $pathId = $this->paths->create($resumeId, $userId, $dto->toPersistPayload());
        $row = $this->paths->findOwned($pathId, $resumeId, $userId);
        $saved = $row !== null ? LearningPathDTO::fromRow($row) : $dto;

        $this->history->append($resumeId, $userId, [
            'path_id' => $pathId,
            'action' => $action,
            'headline' => sprintf(
                '%d-week path · %s · alignment %d/100',
                $saved->timelineWeeks,
                $saved->careerGoal,
                $saved->alignmentScore
            ),
            'progress_percent' => $saved->progressPercent,
            'timeline_weeks' => $saved->timelineWeeks,
            'rules_version' => LearningPathVersion::CURRENT,
            'snapshot_json' => $saved->toHistorySnapshot(),
        ]);

        return [
            'success' => true,
            'message' => sprintf(
                'Learning path ready: %d weeks toward %s.',
                $saved->timelineWeeks,
                $saved->careerGoal
            ),
            'path_id' => $pathId,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildContext(int $resumeId, int $userId, string $goal): array
    {
        $gap = $this->skillGaps->findLatestByResumeId($resumeId, $userId);
        $salary = $this->salary->findLatestByResumeId($resumeId, $userId);
        $coach = $this->coach->findByResumeId($resumeId);
        $intel = $this->intelligence->findLatestByResumeId($resumeId);
        $pro = $this->professional->findByResumeId($resumeId) ?? [];

        if ($goal === '') {
            $goal = trim((string) ($coach['target_role'] ?? $pro['current_job_title'] ?? 'Career advancement'));
            if ($goal === '') {
                $goal = 'Career advancement';
            }
        }

        $gapJson = [];
        if (is_array($gap) && isset($gap['analysis_json'])) {
            $decoded = is_string($gap['analysis_json'])
                ? json_decode($gap['analysis_json'], true)
                : $gap['analysis_json'];
            $gapJson = is_array($decoded) ? $decoded : [];
        }
        $comparison = is_array($gapJson['comparison'] ?? null) ? $gapJson['comparison'] : [];

        $skillNames = [];
        foreach ($this->skills->listByResumeId($resumeId) as $row) {
            $n = trim((string) ($row['skill_name'] ?? $row['name'] ?? ''));
            if ($n !== '') {
                $skillNames[] = $n;
            }
        }

        return [
            'career_goal' => $goal,
            'job_id' => isset($gap['job_id']) ? (int) $gap['job_id'] : null,
            'missing_skills' => $comparison['missing_skills'] ?? [],
            'matched_skills' => $comparison['matched_skills'] ?? [],
            'priority_learning' => $gapJson['priority_learning_order'] ?? [],
            'gap_percentage' => (int) ($gap['gap_percentage'] ?? 0),
            'readiness_score' => (int) ($gap['readiness_score'] ?? $intel['overall_score'] ?? 0),
            'salary_target' => (float) ($salary['recommended_target'] ?? $salary['predicted_salary'] ?? 0),
            'salary_currency' => (string) ($salary['currency'] ?? $pro['preferred_currency'] ?? 'USD'),
            'coach_target_role' => (string) ($coach['target_role'] ?? ''),
            'resume_skills' => $skillNames,
            'resume_overall' => (int) ($intel['overall_score'] ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     */
    private function requireOwned(array $actor, int $resumeId, string $mode): \JobVisa\App\Domain\Resume\Aggregates\ResumeAggregate
    {
        $aggregate = $this->resumes->findAggregateById($resumeId);
        if ($aggregate === null || $aggregate->resume()->deletedAt() !== null) {
            throw LearningPathException::resumeNotFound();
        }
        $resume = $aggregate->resume();
        $allowed = match ($mode) {
            'generate' => $this->policy->canGenerate($actor, $resume),
            'history' => $this->policy->canManageHistory($actor, $resume),
            'export' => $this->policy->canExport($actor, $resume),
            default => $this->policy->canView($actor, $resume),
        };
        if (!$allowed) {
            throw LearningPathException::forbidden();
        }

        return $aggregate;
    }
}

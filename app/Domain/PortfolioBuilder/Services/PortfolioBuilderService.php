<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\PortfolioBuilder\Services;

use JobVisa\App\Domain\PortfolioBuilder\DTO\PortfolioPlanDTO;
use JobVisa\App\Domain\PortfolioBuilder\Exceptions\PortfolioBuilderException;
use JobVisa\App\Domain\PortfolioBuilder\Policies\PortfolioBuilderPolicy;
use JobVisa\App\Domain\PortfolioBuilder\Support\PortfolioBuilderVersion;
use JobVisa\App\Domain\PortfolioBuilder\Validators\PortfolioBuilderValidator;
use JobVisa\App\Domain\Resume\Repositories\ResumeRepositoryInterface;
use JobVisa\App\Domain\Resume\Support\ResumeCompletionCalculator;
use JobVisa\App\Repositories\Contracts\CareerCoachSessionRepositoryInterface;
use JobVisa\App\Repositories\Contracts\LearningPathRepositoryInterface;
use JobVisa\App\Repositories\Contracts\PortfolioBuilderHistoryRepositoryInterface;
use JobVisa\App\Repositories\Contracts\PortfolioBuilderPlanRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeIntelligenceRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeJobMatchRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumePortfolioRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeProfessionalRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeProjectRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeSkillRepositoryInterface;
use JobVisa\App\Repositories\Contracts\SkillGapAnalysisRepositoryInterface;

/**
 * AI Portfolio & Project Builder application service.
 */
final class PortfolioBuilderService
{
    public function __construct(
        private readonly ResumeRepositoryInterface $resumes,
        private readonly PortfolioBuilderPlanRepositoryInterface $plans,
        private readonly PortfolioBuilderHistoryRepositoryInterface $history,
        private readonly PortfolioBuilderAnalyzer $analyzer,
        private readonly PortfolioBuilderPdfExporter $pdfExporter,
        private readonly SkillGapAnalysisRepositoryInterface $skillGaps,
        private readonly LearningPathRepositoryInterface $learningPaths,
        private readonly CareerCoachSessionRepositoryInterface $coach,
        private readonly ResumeIntelligenceRepositoryInterface $intelligence,
        private readonly ResumeProfessionalRepositoryInterface $professional,
        private readonly ResumeSkillRepositoryInterface $skills,
        private readonly ResumeProjectRepositoryInterface $projects,
        private readonly ResumePortfolioRepositoryInterface $portfolio,
        private readonly ResumeJobMatchRepositoryInterface $matches,
        private readonly ResumeCompletionCalculator $completion,
        private readonly PortfolioBuilderPolicy $policy,
        private readonly PortfolioBuilderValidator $validator,
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

        $row = $this->plans->findLatestByResumeId($resumeId, $userId);
        $plan = $row !== null ? PortfolioPlanDTO::fromRow($row) : null;

        $pro = $this->professional->findByResumeId($resumeId) ?? [];
        $coach = $this->coach->findByResumeId($resumeId);
        $defaultGoal = trim((string) ($coach['target_role'] ?? $pro['current_job_title'] ?? $pro['headline'] ?? ''));

        return [
            'resume' => [
                'id' => $resume->id(),
                'title' => $resume->title(),
            ],
            'completion' => $this->completion->evaluate($userId, $resumeId),
            'plan' => $plan,
            'default_career_goal' => $defaultGoal,
            'versions' => $this->plans->listByResumeId($resumeId, $userId, 12),
            'history' => $this->history->listByResumeId($resumeId, 20),
            'deleted_history' => $this->history->listDeletedByResumeId($resumeId, 10),
            'can_edit' => $this->policy->canGenerate($actor, $resume),
            'version' => PortfolioBuilderVersion::CURRENT,
            'disclaimer' => 'Portfolio recommendations are generated from your resume, skill-gap, learning path and job-match signals using deterministic rules. Guidance only — not hiring advice.',
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
            'versions' => $this->plans->listByResumeId($resumeId, (int) $actor['id'], 30),
            'version' => PortfolioBuilderVersion::CURRENT,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, plan_id: int}
     */
    public function generate(array $actor, int $resumeId, ?string $careerGoal = null): array
    {
        return $this->runGenerate($actor, $resumeId, $careerGoal, 'generate');
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, plan_id: int}
     */
    public function recalculate(array $actor, int $resumeId, ?string $careerGoal = null): array
    {
        return $this->runGenerate($actor, $resumeId, $careerGoal, 'recalculate');
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
    public function exportPdf(array $actor, int $resumeId, int $planId): array
    {
        $this->validator->assertResumeId($resumeId);
        $this->validator->assertPlanId($planId);
        $this->requireOwned($actor, $resumeId, 'export');
        $row = $this->plans->findOwned($planId, $resumeId, (int) $actor['id']);
        if ($row === null) {
            throw PortfolioBuilderException::planNotFound();
        }
        $dto = PortfolioPlanDTO::fromRow($row);
        $content = $this->pdfExporter->export($dto);

        return [
            'filename' => 'portfolio-builder-' . $planId . '.pdf',
            'mime' => 'application/pdf',
            'content' => $content,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, plan_id: int}
     */
    private function runGenerate(array $actor, int $resumeId, ?string $careerGoal, string $action): array
    {
        $this->validator->assertResumeId($resumeId);
        $this->requireOwned($actor, $resumeId, 'generate');
        $userId = (int) $actor['id'];
        $goal = $this->validator->normalizeCareerGoal($careerGoal);
        $context = $this->buildContext($resumeId, $userId, $goal);
        $dto = $this->analyzer->generate($resumeId, $userId, $context);
        $planId = $this->plans->create($resumeId, $userId, $dto->toPersistPayload());
        $row = $this->plans->findOwned($planId, $resumeId, $userId);
        $saved = $row !== null ? PortfolioPlanDTO::fromRow($row) : $dto;

        $this->history->append($resumeId, $userId, [
            'plan_id' => $planId,
            'action' => $action,
            'headline' => sprintf(
                'Strength %d/100 · %d projects · %s',
                $saved->strengthScore,
                $saved->projectCount,
                $saved->careerGoal
            ),
            'strength_score' => $saved->strengthScore,
            'project_count' => $saved->projectCount,
            'rules_version' => PortfolioBuilderVersion::CURRENT,
            'snapshot_json' => $saved->toHistorySnapshot(),
        ]);

        return [
            'success' => true,
            'message' => sprintf(
                'Portfolio plan ready: %d projects (strength %d/100).',
                $saved->projectCount,
                $saved->strengthScore
            ),
            'plan_id' => $planId,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildContext(int $resumeId, int $userId, string $goal): array
    {
        $gap = $this->skillGaps->findLatestByResumeId($resumeId, $userId);
        $lp = $this->learningPaths->findLatestByResumeId($resumeId, $userId);
        $coach = $this->coach->findByResumeId($resumeId);
        $intel = $this->intelligence->findLatestByResumeId($resumeId);
        $pro = $this->professional->findByResumeId($resumeId) ?? [];
        $topMatch = $this->matches->listTopForResume($resumeId, 1);

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

        $lpJson = [];
        if (is_array($lp) && isset($lp['path_json'])) {
            $decoded = is_string($lp['path_json'])
                ? json_decode($lp['path_json'], true)
                : $lp['path_json'];
            $lpJson = is_array($decoded) ? $decoded : [];
        }

        $skillNames = [];
        foreach ($this->skills->listByResumeId($resumeId) as $row) {
            $n = trim((string) ($row['skill_name'] ?? $row['name'] ?? ''));
            if ($n !== '') {
                $skillNames[] = $n;
            }
        }

        return [
            'career_goal' => $goal,
            'job_id' => isset($gap['job_id']) ? (int) $gap['job_id'] : (isset($topMatch[0]['job_id']) ? (int) $topMatch[0]['job_id'] : null),
            'target_job_title' => (string) ($gap['job_title'] ?? $topMatch[0]['job_title'] ?? ''),
            'missing_skills' => $comparison['missing_skills'] ?? [],
            'matched_skills' => $comparison['matched_skills'] ?? [],
            'gap_percentage' => (int) ($gap['gap_percentage'] ?? 0),
            'resume_skills' => $skillNames,
            'resume_overall' => (int) ($intel['overall_score'] ?? 0),
            'existing_project_count' => $this->projects->countActive($resumeId),
            'existing_portfolio_count' => $this->portfolio->countActive($resumeId),
            'learning_projects' => $lpJson['practice_projects'] ?? [],
            'learning_path_weeks' => (int) ($lp['timeline_weeks'] ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     */
    private function requireOwned(array $actor, int $resumeId, string $mode): \JobVisa\App\Domain\Resume\Aggregates\ResumeAggregate
    {
        $aggregate = $this->resumes->findAggregateById($resumeId);
        if ($aggregate === null || $aggregate->resume()->deletedAt() !== null) {
            throw PortfolioBuilderException::resumeNotFound();
        }
        $resume = $aggregate->resume();
        $allowed = match ($mode) {
            'generate' => $this->policy->canGenerate($actor, $resume),
            'history' => $this->policy->canManageHistory($actor, $resume),
            'export' => $this->policy->canExport($actor, $resume),
            default => $this->policy->canView($actor, $resume),
        };
        if (!$allowed) {
            throw PortfolioBuilderException::forbidden();
        }

        return $aggregate;
    }
}

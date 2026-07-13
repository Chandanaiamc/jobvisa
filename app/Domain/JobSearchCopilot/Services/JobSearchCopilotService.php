<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\JobSearchCopilot\Services;

use JobVisa\App\Domain\JobSearchCopilot\DTO\JobSearchCopilotPlanDTO;
use JobVisa\App\Domain\JobSearchCopilot\Exceptions\JobSearchCopilotException;
use JobVisa\App\Domain\JobSearchCopilot\Policies\JobSearchCopilotPolicy;
use JobVisa\App\Domain\JobSearchCopilot\Support\JobSearchCopilotVersion;
use JobVisa\App\Domain\JobSearchCopilot\Validators\JobSearchCopilotValidator;
use JobVisa\App\Domain\Resume\Aggregates\ResumeAggregate;
use JobVisa\App\Domain\Resume\Repositories\ResumeRepositoryInterface;
use JobVisa\App\Domain\Resume\Support\ResumeCompletionCalculator;
use JobVisa\App\Repositories\Contracts\CareerCoachSessionRepositoryInterface;
use JobVisa\App\Repositories\Contracts\JobRepositoryInterface;
use JobVisa\App\Repositories\Contracts\JobSearchCopilotHistoryRepositoryInterface;
use JobVisa\App\Repositories\Contracts\JobSearchCopilotPlanRepositoryInterface;
use JobVisa\App\Repositories\Contracts\MockInterviewSessionRepositoryInterface;
use JobVisa\App\Repositories\Contracts\PortfolioBuilderPlanRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeIntelligenceRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeJobMatchRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeProfessionalRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeSkillRepositoryInterface;
use JobVisa\App\Repositories\Contracts\SalaryIntelligencePredictionRepositoryInterface;
use JobVisa\App\Repositories\Contracts\SkillGapAnalysisRepositoryInterface;

/**
 * AI Job Search Copilot application service.
 */
final class JobSearchCopilotService
{
    public function __construct(
        private readonly ResumeRepositoryInterface $resumes,
        private readonly JobSearchCopilotPlanRepositoryInterface $plans,
        private readonly JobSearchCopilotHistoryRepositoryInterface $history,
        private readonly JobSearchCopilotAnalyzer $analyzer,
        private readonly JobSearchCopilotPdfExporter $pdfExporter,
        private readonly JobRepositoryInterface $jobs,
        private readonly ResumeJobMatchRepositoryInterface $matches,
        private readonly SkillGapAnalysisRepositoryInterface $skillGaps,
        private readonly SalaryIntelligencePredictionRepositoryInterface $salary,
        private readonly PortfolioBuilderPlanRepositoryInterface $portfolioPlans,
        private readonly MockInterviewSessionRepositoryInterface $mockInterviews,
        private readonly CareerCoachSessionRepositoryInterface $coach,
        private readonly ResumeIntelligenceRepositoryInterface $intelligence,
        private readonly ResumeProfessionalRepositoryInterface $professional,
        private readonly ResumeSkillRepositoryInterface $skills,
        private readonly ResumeCompletionCalculator $completion,
        private readonly JobSearchCopilotPolicy $policy,
        private readonly JobSearchCopilotValidator $validator,
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
        $plan = $row !== null ? JobSearchCopilotPlanDTO::fromRow($row) : null;

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
            'version' => JobSearchCopilotVersion::CURRENT,
            'disclaimer' => 'Job Search Copilot recommendations are generated from your resume, published jobs and prior AI signals using deterministic rules. Guidance only — not job guarantees.',
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
            'version' => JobSearchCopilotVersion::CURRENT,
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
            throw JobSearchCopilotException::planNotFound();
        }
        $dto = JobSearchCopilotPlanDTO::fromRow($row);
        $content = $this->pdfExporter->export($dto);

        return [
            'filename' => 'job-search-copilot-' . $planId . '.pdf',
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
        $saved = $row !== null ? JobSearchCopilotPlanDTO::fromRow($row) : $dto;

        $this->history->append($resumeId, $userId, [
            'plan_id' => $planId,
            'action' => $action,
            'headline' => sprintf(
                'Score %d/100 · %d recommendations · %s',
                $saved->copilotScore,
                $saved->recommendationCount,
                $saved->careerGoal
            ),
            'copilot_score' => $saved->copilotScore,
            'recommendation_count' => $saved->recommendationCount,
            'rules_version' => JobSearchCopilotVersion::CURRENT,
            'snapshot_json' => $saved->toHistorySnapshot(),
        ]);

        return [
            'success' => true,
            'message' => sprintf(
                'Job search plan ready: %d recommendations (copilot %d/100).',
                $saved->recommendationCount,
                $saved->copilotScore
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
        $salary = $this->salary->findLatestByResumeId($resumeId, $userId);
        $plan = $this->portfolioPlans->findLatestByResumeId($resumeId, $userId);
        $mock = $this->mockInterviews->findLatestByResumeId($resumeId, $userId);
        $coach = $this->coach->findByResumeId($resumeId);
        $intel = $this->intelligence->findLatestByResumeId($resumeId);
        $pro = $this->professional->findByResumeId($resumeId) ?? [];
        $matchSnapshots = $this->matches->listTopForResume($resumeId, 20);
        $published = $this->jobs->findPublished(40);

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

        $years = isset($pro['years_of_experience']) && $pro['years_of_experience'] !== null && $pro['years_of_experience'] !== ''
            ? (float) $pro['years_of_experience']
            : 0.0;

        $salaryMid = 0;
        if (is_array($salary)) {
            $salaryMid = (int) ($salary['predicted_salary'] ?? $salary['min_salary'] ?? 0);
        }

        $locations = [];
        if (!empty($pro['preferred_location'])) {
            $locations[] = (string) $pro['preferred_location'];
        }
        if (!empty($pro['city'])) {
            $locations[] = (string) $pro['city'];
        }

        return [
            'career_goal' => $goal,
            'years' => $years,
            'resume_skills' => $skillNames,
            'missing_skills' => $comparison['missing_skills'] ?? [],
            'matched_skills' => $comparison['matched_skills'] ?? [],
            'gap_percentage' => (int) ($gap['gap_percentage'] ?? 0),
            'resume_overall' => (int) ($intel['overall_score'] ?? 0),
            'portfolio_strength' => (int) ($plan['strength_score'] ?? 0),
            'mock_overall' => (int) ($mock['overall_score'] ?? 0),
            'salary_mid' => $salaryMid,
            'preferred_locations' => $locations !== [] ? $locations : ['Sri Lanka', 'Remote', 'Gulf'],
            'jobs' => $published,
            'match_snapshots' => $matchSnapshots,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     */
    private function requireOwned(array $actor, int $resumeId, string $mode): ResumeAggregate
    {
        $aggregate = $this->resumes->findAggregateById($resumeId);
        if ($aggregate === null || $aggregate->resume()->deletedAt() !== null) {
            throw JobSearchCopilotException::resumeNotFound();
        }
        $resume = $aggregate->resume();
        $allowed = match ($mode) {
            'generate' => $this->policy->canGenerate($actor, $resume),
            'history' => $this->policy->canManageHistory($actor, $resume),
            'export' => $this->policy->canExport($actor, $resume),
            default => $this->policy->canView($actor, $resume),
        };
        if (!$allowed) {
            throw JobSearchCopilotException::forbidden();
        }

        return $aggregate;
    }
}

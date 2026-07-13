<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\OfferEvaluation\Services;

use JobVisa\App\Domain\OfferEvaluation\DTO\OfferEvaluationAnalysisDTO;
use JobVisa\App\Domain\OfferEvaluation\Exceptions\OfferEvaluationException;
use JobVisa\App\Domain\OfferEvaluation\Policies\OfferEvaluationPolicy;
use JobVisa\App\Domain\OfferEvaluation\Support\OfferEvaluationVersion;
use JobVisa\App\Domain\OfferEvaluation\Validators\OfferEvaluationValidator;
use JobVisa\App\Domain\Resume\Aggregates\ResumeAggregate;
use JobVisa\App\Domain\Resume\Repositories\ResumeRepositoryInterface;
use JobVisa\App\Domain\Resume\Support\ResumeCompletionCalculator;
use JobVisa\App\Repositories\Contracts\CareerCoachSessionRepositoryInterface;
use JobVisa\App\Repositories\Contracts\JobRepositoryInterface;
use JobVisa\App\Repositories\Contracts\JobSearchCopilotPlanRepositoryInterface;
use JobVisa\App\Repositories\Contracts\MockInterviewSessionRepositoryInterface;
use JobVisa\App\Repositories\Contracts\OfferEvaluationAnalysisRepositoryInterface;
use JobVisa\App\Repositories\Contracts\OfferEvaluationHistoryRepositoryInterface;
use JobVisa\App\Repositories\Contracts\PortfolioBuilderPlanRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeIntelligenceRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeProfessionalRepositoryInterface;
use JobVisa\App\Repositories\Contracts\SalaryIntelligencePredictionRepositoryInterface;
use JobVisa\App\Repositories\Contracts\SkillGapAnalysisRepositoryInterface;

/**
 * AI Offer Evaluation Assistant application service.
 */
final class OfferEvaluationService
{
    public function __construct(
        private readonly ResumeRepositoryInterface $resumes,
        private readonly OfferEvaluationAnalysisRepositoryInterface $analyses,
        private readonly OfferEvaluationHistoryRepositoryInterface $history,
        private readonly OfferEvaluationAnalyzer $analyzer,
        private readonly OfferEvaluationPdfExporter $pdfExporter,
        private readonly JobRepositoryInterface $jobs,
        private readonly SalaryIntelligencePredictionRepositoryInterface $salary,
        private readonly SkillGapAnalysisRepositoryInterface $skillGaps,
        private readonly PortfolioBuilderPlanRepositoryInterface $portfolioPlans,
        private readonly MockInterviewSessionRepositoryInterface $mockInterviews,
        private readonly JobSearchCopilotPlanRepositoryInterface $copilotPlans,
        private readonly CareerCoachSessionRepositoryInterface $coach,
        private readonly ResumeIntelligenceRepositoryInterface $intelligence,
        private readonly ResumeProfessionalRepositoryInterface $professional,
        private readonly ResumeCompletionCalculator $completion,
        private readonly OfferEvaluationPolicy $policy,
        private readonly OfferEvaluationValidator $validator,
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

        $row = $this->analyses->findLatestByResumeId($resumeId, $userId);
        $analysis = $row !== null ? OfferEvaluationAnalysisDTO::fromRow($row) : null;

        $pro = $this->professional->findByResumeId($resumeId) ?? [];
        $coach = $this->coach->findByResumeId($resumeId);
        $salary = $this->salary->findLatestByResumeId($resumeId, $userId);
        $defaultTitle = trim((string) ($coach['target_role'] ?? $pro['current_job_title'] ?? $pro['headline'] ?? ''));

        $matchedJobs = [];
        foreach ($this->jobs->findPublished(15) as $job) {
            $matchedJobs[] = [
                'job_id' => (int) ($job['id'] ?? 0),
                'job_title' => (string) ($job['title'] ?? ''),
            ];
        }

        return [
            'resume' => [
                'id' => $resume->id(),
                'title' => $resume->title(),
            ],
            'completion' => $this->completion->evaluate($userId, $resumeId),
            'analysis' => $analysis,
            'matched_jobs' => $matchedJobs,
            'defaults' => [
                'job_title' => $defaultTitle,
                'currency' => (string) ($salary['currency'] ?? 'USD'),
                'base_salary' => (float) ($salary['predicted_salary'] ?? 0),
                'location' => (string) ($pro['city'] ?? $pro['preferred_location'] ?? ''),
            ],
            'versions' => $this->analyses->listByResumeId($resumeId, $userId, 12),
            'history' => $this->history->listByResumeId($resumeId, 20),
            'deleted_history' => $this->history->listDeletedByResumeId($resumeId, 10),
            'can_edit' => $this->policy->canEvaluate($actor, $resume),
            'version' => OfferEvaluationVersion::CURRENT,
            'disclaimer' => 'Offer evaluations compare your inputs to resume and salary-intelligence signals using deterministic rules. Guidance only — not legal, tax or financial advice.',
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
            'versions' => $this->analyses->listByResumeId($resumeId, (int) $actor['id'], 30),
            'version' => OfferEvaluationVersion::CURRENT,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, analysis_id: int}
     */
    public function evaluate(array $actor, int $resumeId, array $input): array
    {
        return $this->runEvaluate($actor, $resumeId, $input, 'evaluate');
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, analysis_id: int}
     */
    public function recalculate(array $actor, int $resumeId, array $input): array
    {
        return $this->runEvaluate($actor, $resumeId, $input, 'recalculate');
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
    public function exportPdf(array $actor, int $resumeId, int $analysisId): array
    {
        $this->validator->assertResumeId($resumeId);
        $this->validator->assertAnalysisId($analysisId);
        $this->requireOwned($actor, $resumeId, 'export');
        $row = $this->analyses->findOwned($analysisId, $resumeId, (int) $actor['id']);
        if ($row === null) {
            throw OfferEvaluationException::analysisNotFound();
        }
        $dto = OfferEvaluationAnalysisDTO::fromRow($row);
        $content = $this->pdfExporter->export($dto);

        return [
            'filename' => 'offer-evaluation-' . $analysisId . '.pdf',
            'mime' => 'application/pdf',
            'content' => $content,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, analysis_id: int}
     */
    private function runEvaluate(array $actor, int $resumeId, array $input, string $action): array
    {
        $this->validator->assertResumeId($resumeId);
        $this->requireOwned($actor, $resumeId, 'evaluate');
        $userId = (int) $actor['id'];
        $offer = $this->validator->normalizeOfferInput($input);

        if ($offer['job_id'] !== null) {
            $job = $this->jobs->findPublishedRecordById((int) $offer['job_id']);
            if ($job !== null) {
                if ($offer['job_title'] === '') {
                    $offer['job_title'] = (string) ($job['title'] ?? '');
                }
                if ($offer['location'] === '' && !empty($job['country_name'])) {
                    $offer['location'] = (string) $job['country_name'];
                }
            }
        }

        $context = $this->buildContext($resumeId, $userId, $offer);
        $dto = $this->analyzer->evaluate($resumeId, $userId, $offer, $context);
        $analysisId = $this->analyses->create($resumeId, $userId, $dto->toPersistPayload());
        $row = $this->analyses->findOwned($analysisId, $resumeId, $userId);
        $saved = $row !== null ? OfferEvaluationAnalysisDTO::fromRow($row) : $dto;

        $this->history->append($resumeId, $userId, [
            'analysis_id' => $analysisId,
            'action' => $action,
            'headline' => sprintf(
                '%s · %d/100 · %s',
                $saved->jobTitle,
                $saved->overallScore,
                strtoupper($saved->recommendation)
            ),
            'overall_score' => $saved->overallScore,
            'recommendation' => $saved->recommendation,
            'rules_version' => OfferEvaluationVersion::CURRENT,
            'snapshot_json' => $saved->toHistorySnapshot(),
        ]);

        return [
            'success' => true,
            'message' => sprintf(
                'Offer evaluated: %d/100 (%s).',
                $saved->overallScore,
                $saved->recommendation
            ),
            'analysis_id' => $analysisId,
        ];
    }

    /**
     * @param  array<string, mixed>  $offer
     * @return array<string, mixed>
     */
    private function buildContext(int $resumeId, int $userId, array $offer): array
    {
        $salary = $this->salary->findLatestByResumeId($resumeId, $userId);
        $gap = $this->skillGaps->findLatestByResumeId($resumeId, $userId);
        $plan = $this->portfolioPlans->findLatestByResumeId($resumeId, $userId);
        $mock = $this->mockInterviews->findLatestByResumeId($resumeId, $userId);
        $copilot = $this->copilotPlans->findLatestByResumeId($resumeId, $userId);
        $coach = $this->coach->findByResumeId($resumeId);
        $intel = $this->intelligence->findLatestByResumeId($resumeId);
        $pro = $this->professional->findByResumeId($resumeId) ?? [];

        $market = is_array($salary) ? (float) ($salary['predicted_salary'] ?? 0) : 0.0;
        $marketMin = is_array($salary) ? (float) ($salary['min_salary'] ?? 0) : 0.0;
        $marketMax = is_array($salary) ? (float) ($salary['max_salary'] ?? 0) : 0.0;

        $locations = [];
        if (!empty($pro['preferred_location'])) {
            $locations[] = (string) $pro['preferred_location'];
        }
        if (!empty($pro['city'])) {
            $locations[] = (string) $pro['city'];
        }
        if ($offer['location'] !== '') {
            $locations[] = (string) $offer['location'];
        }

        return [
            'career_goal' => trim((string) ($coach['target_role'] ?? $pro['current_job_title'] ?? $offer['job_title'] ?? '')),
            'market_salary' => $market,
            'market_min' => $marketMin,
            'market_max' => $marketMax,
            'resume_overall' => (int) ($intel['overall_score'] ?? 0),
            'gap_percentage' => (int) ($gap['gap_percentage'] ?? 0),
            'portfolio_strength' => (int) ($plan['strength_score'] ?? 0),
            'mock_overall' => (int) ($mock['overall_score'] ?? 0),
            'copilot_score' => (int) ($copilot['copilot_score'] ?? 0),
            'preferred_locations' => $locations !== [] ? array_values(array_unique($locations)) : ['Sri Lanka', 'Remote', 'Gulf'],
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     */
    private function requireOwned(array $actor, int $resumeId, string $mode): ResumeAggregate
    {
        $aggregate = $this->resumes->findAggregateById($resumeId);
        if ($aggregate === null || $aggregate->resume()->deletedAt() !== null) {
            throw OfferEvaluationException::resumeNotFound();
        }
        $resume = $aggregate->resume();
        $allowed = match ($mode) {
            'evaluate' => $this->policy->canEvaluate($actor, $resume),
            'history' => $this->policy->canManageHistory($actor, $resume),
            'export' => $this->policy->canExport($actor, $resume),
            default => $this->policy->canView($actor, $resume),
        };
        if (!$allowed) {
            throw OfferEvaluationException::forbidden();
        }

        return $aggregate;
    }
}

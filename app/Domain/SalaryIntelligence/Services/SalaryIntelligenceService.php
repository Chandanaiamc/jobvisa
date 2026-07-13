<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\SalaryIntelligence\Services;

use JobVisa\App\Domain\Resume\Repositories\ResumeRepositoryInterface;
use JobVisa\App\Domain\Resume\Support\ResumeCompletionCalculator;
use JobVisa\App\Domain\SalaryIntelligence\DTO\SalaryPredictionDTO;
use JobVisa\App\Domain\SalaryIntelligence\Exceptions\SalaryIntelligenceException;
use JobVisa\App\Domain\SalaryIntelligence\Policies\SalaryIntelligencePolicy;
use JobVisa\App\Domain\SalaryIntelligence\Support\SalaryIntelligenceVersion;
use JobVisa\App\Domain\SalaryIntelligence\Validators\SalaryIntelligenceValidator;
use JobVisa\App\Repositories\Contracts\EducationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeCertificationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumePersonalRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeProfessionalRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeSkillRepositoryInterface;
use JobVisa\App\Repositories\Contracts\SalaryIntelligenceHistoryRepositoryInterface;
use JobVisa\App\Repositories\Contracts\SalaryIntelligencePredictionRepositoryInterface;
use JobVisa\App\Repositories\Contracts\WorkExperienceRepositoryInterface;
use JobVisa\App\Repositories\SalaryMarketSampleRepository;

/**
 * AI Salary Intelligence application service.
 */
final class SalaryIntelligenceService
{
    public function __construct(
        private readonly ResumeRepositoryInterface $resumes,
        private readonly SalaryIntelligencePredictionRepositoryInterface $predictions,
        private readonly SalaryIntelligenceHistoryRepositoryInterface $history,
        private readonly SalaryIntelligencePredictor $predictor,
        private readonly SalaryIntelligencePdfExporter $pdfExporter,
        private readonly SalaryMarketSampleRepository $market,
        private readonly ResumeProfessionalRepositoryInterface $professional,
        private readonly ResumePersonalRepositoryInterface $personal,
        private readonly ResumeSkillRepositoryInterface $skills,
        private readonly EducationRepositoryInterface $education,
        private readonly ResumeCertificationRepositoryInterface $certifications,
        private readonly WorkExperienceRepositoryInterface $experience,
        private readonly ResumeCompletionCalculator $completion,
        private readonly SalaryIntelligencePolicy $policy,
        private readonly SalaryIntelligenceValidator $validator,
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

        $row = $this->predictions->findLatestByResumeId($resumeId, $userId);
        $prediction = $row !== null ? SalaryPredictionDTO::fromRow($row) : null;

        return [
            'resume' => [
                'id' => $resume->id(),
                'title' => $resume->title(),
            ],
            'completion' => $this->completion->evaluate($userId, $resumeId),
            'prediction' => $prediction,
            'versions' => $this->predictions->listByResumeId($resumeId, $userId, 12),
            'history' => $this->history->listByResumeId($resumeId, 20),
            'deleted_history' => $this->history->listDeletedByResumeId($resumeId, 10),
            'can_edit' => $this->policy->canCalculate($actor, $resume),
            'version' => SalaryIntelligenceVersion::CURRENT,
            'disclaimer' => 'Salary Intelligence estimates ranges from resume signals and published job salary bands. Deterministic heuristics only — not a formal market survey or offer.',
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
            'versions' => $this->predictions->listByResumeId($resumeId, (int) $actor['id'], 30),
            'version' => SalaryIntelligenceVersion::CURRENT,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, prediction_id: int}
     */
    public function calculate(array $actor, int $resumeId): array
    {
        return $this->runPrediction($actor, $resumeId, 'calculate');
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, prediction_id: int}
     */
    public function recalculate(array $actor, int $resumeId): array
    {
        return $this->runPrediction($actor, $resumeId, 'recalculate');
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
    public function exportPdf(array $actor, int $resumeId, int $predictionId): array
    {
        $this->validator->assertResumeId($resumeId);
        $this->validator->assertPredictionId($predictionId);
        $this->requireOwned($actor, $resumeId, 'export');
        $row = $this->predictions->findOwned($predictionId, $resumeId, (int) $actor['id']);
        if ($row === null) {
            throw SalaryIntelligenceException::predictionNotFound();
        }
        $dto = SalaryPredictionDTO::fromRow($row);
        $content = $this->pdfExporter->export($dto);

        return [
            'filename' => 'salary-intelligence-' . $predictionId . '.pdf',
            'mime' => 'application/pdf',
            'content' => $content,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, prediction_id: int}
     */
    private function runPrediction(array $actor, int $resumeId, string $action): array
    {
        $this->validator->assertResumeId($resumeId);
        $aggregate = $this->requireOwned($actor, $resumeId, 'calculate');
        $userId = (int) $actor['id'];
        $context = $this->buildContext($resumeId);
        $dto = $this->predictor->predict($resumeId, $userId, $context);
        $predictionId = $this->predictions->create($resumeId, $userId, $dto->toPersistPayload());
        $row = $this->predictions->findOwned($predictionId, $resumeId, $userId);
        $saved = $row !== null ? SalaryPredictionDTO::fromRow($row) : $dto;

        $this->history->append($resumeId, $userId, [
            'prediction_id' => $predictionId,
            'action' => $action,
            'headline' => sprintf(
                '%s %s · %s (%s)',
                $saved->currency,
                number_format($saved->predictedSalary, 0),
                $saved->careerLevel,
                $saved->jobTitle
            ),
            'predicted_salary' => $saved->predictedSalary,
            'currency' => $saved->currency,
            'confidence_score' => $saved->confidenceScore,
            'rules_version' => SalaryIntelligenceVersion::CURRENT,
            'snapshot_json' => $saved->toHistorySnapshot(),
        ]);

        return [
            'success' => true,
            'message' => sprintf(
                'Salary estimate ready: %s %s (confidence %d/100).',
                $saved->currency,
                number_format($saved->predictedSalary, 0),
                $saved->confidenceScore
            ),
            'prediction_id' => $predictionId,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildContext(int $resumeId): array
    {
        $pro = $this->professional->findByResumeId($resumeId) ?? [];
        $personal = $this->personal->findByResumeId($resumeId) ?? [];

        $skillNames = [];
        foreach ($this->skills->listByResumeId($resumeId) as $row) {
            $n = trim((string) ($row['skill_name'] ?? $row['name'] ?? ''));
            if ($n !== '') {
                $skillNames[] = $n;
            }
        }

        $premium = [];
        foreach ($skillNames as $name) {
            $l = mb_strtolower($name);
            if (preg_match('/\\b(python|java|react|aws|azure|kubernetes|nursing|icu|pmp|cpa|sap|ml|ai|docker|golang)\\b/u', $l)) {
                $premium[] = $name;
            }
        }

        $eduRank = 0;
        $eduLabel = 'Not specified';
        foreach ($this->education->listByResumeId($resumeId) as $row) {
            $rank = $this->educationRank(
                (string) ($row['qualification_type'] ?? ''),
                (string) ($row['degree'] ?? '')
            );
            if ($rank > $eduRank) {
                $eduRank = $rank;
                $eduLabel = trim((string) ($row['degree'] ?? $row['qualification_type'] ?? 'Education'));
            }
        }

        $certCount = 0;
        foreach ($this->certifications->listByResumeId($resumeId) as $row) {
            if (($row['deleted_at'] ?? null) === null) {
                $certCount++;
            }
        }

        $years = (float) ($pro['years_of_experience'] ?? 0);
        if ($years <= 0) {
            $years = $this->estimateYearsFromExperience($resumeId);
        }

        $title = trim((string) ($pro['current_job_title'] ?? ''));
        if ($title === '') {
            $title = $this->firstExperienceTitle($resumeId) ?: 'Professional';
        }

        $currency = strtoupper(trim((string) ($pro['preferred_currency'] ?? $personal['salary_currency'] ?? 'USD')));
        if ($currency === '') {
            $currency = 'USD';
        }

        $location = $this->resolveLocation($resumeId, $personal);
        $industry = trim((string) ($pro['industry'] ?? ''));
        if ($industry === '') {
            $industry = 'General';
        }

        $market = $this->market->samplePublished($title, $currency, 25);

        return [
            'years' => $years,
            'skill_count' => count($skillNames),
            'premium_skills' => $premium,
            'cert_count' => $certCount,
            'education_rank' => $eduRank,
            'education_label' => $eduLabel,
            'job_title' => $title,
            'industry' => $industry,
            'location_label' => $location,
            'currency' => $market['currency'] !== '' ? $market['currency'] : $currency,
            'expected_salary' => (float) ($pro['expected_salary'] ?? 0),
            'current_salary' => (float) ($pro['current_salary'] ?? 0),
            'market_from_jobs' => (float) ($market['average'] ?? 0),
            'market_sample_count' => (int) ($market['count'] ?? 0),
        ];
    }

    private function educationRank(string $qualification, string $degree): int
    {
        $blob = mb_strtolower($qualification . ' ' . $degree);
        if (str_contains($blob, 'phd') || str_contains($blob, 'doctor')) {
            return 5;
        }
        if (str_contains($blob, 'master') || str_contains($blob, 'mba') || str_contains($blob, 'msc') || str_contains($blob, 'ma ')) {
            return 4;
        }
        if (str_contains($blob, 'bachelor') || str_contains($blob, 'bsc') || str_contains($blob, 'ba ') || str_contains($blob, 'degree')) {
            return 3;
        }
        if (str_contains($blob, 'diploma') || str_contains($blob, 'associate')) {
            return 2;
        }
        if (trim($blob) !== '') {
            return 1;
        }

        return 0;
    }

    private function estimateYearsFromExperience(int $resumeId): float
    {
        $rows = $this->experience->listByResumeId($resumeId);
        if ($rows === []) {
            return 0.0;
        }
        $years = count($rows) * 1.5;

        return min(25.0, $years);
    }

    private function firstExperienceTitle(int $resumeId): string
    {
        foreach ($this->experience->listByResumeId($resumeId) as $row) {
            $t = trim((string) ($row['job_title'] ?? $row['title'] ?? ''));
            if ($t !== '') {
                return $t;
            }
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $personal
     */
    private function resolveLocation(int $resumeId, array $personal): string
    {
        $ids = $this->personal->listPreferredCountryIds($resumeId);
        if ($ids !== []) {
            $name = $this->market->countryNameById((int) $ids[0]);
            if ($name !== null && $name !== '') {
                return $name;
            }
        }

        $pro = $this->professional->findByResumeId($resumeId) ?? [];
        if (!empty($pro['open_to_remote'])) {
            return 'Remote';
        }

        unset($personal);

        return 'Global';
    }

    /**
     * @param  array<string, mixed>  $actor
     */
    private function requireOwned(array $actor, int $resumeId, string $mode): \JobVisa\App\Domain\Resume\Aggregates\ResumeAggregate
    {
        $aggregate = $this->resumes->findAggregateById($resumeId);
        if ($aggregate === null || $aggregate->resume()->deletedAt() !== null) {
            throw SalaryIntelligenceException::resumeNotFound();
        }
        $resume = $aggregate->resume();
        $allowed = match ($mode) {
            'calculate' => $this->policy->canCalculate($actor, $resume),
            'history' => $this->policy->canManageHistory($actor, $resume),
            'export' => $this->policy->canExport($actor, $resume),
            default => $this->policy->canView($actor, $resume),
        };
        if (!$allowed) {
            throw SalaryIntelligenceException::forbidden();
        }

        return $aggregate;
    }
}

<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\ApplicationAssistant\Services;

use JobVisa\App\Domain\ApplicationAssistant\DTO\ApplicationAnalysisDTO;
use JobVisa\App\Domain\ApplicationAssistant\Exceptions\ApplicationAssistantException;
use JobVisa\App\Domain\ApplicationAssistant\Policies\ApplicationAssistantPolicy;
use JobVisa\App\Domain\ApplicationAssistant\Support\ApplicationAssistantVersion;
use JobVisa\App\Domain\ApplicationAssistant\Validators\ApplicationAssistantValidator;
use JobVisa\App\Domain\JobMatching\Services\JobMatchContextFactory;
use JobVisa\App\Domain\JobMatching\Services\JobMatchScoringService;
use JobVisa\App\Domain\Resume\Repositories\ResumeRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ApplicationAssistantAnalysisRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ApplicationAssistantHistoryRepositoryInterface;
use JobVisa\App\Repositories\Contracts\JobRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeAchievementRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeIntelligenceRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeJobMatchRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumePortfolioRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeProfessionalRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeProjectRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeSkillRepositoryInterface;
use JobVisa\App\Repositories\Contracts\WorkExperienceRepositoryInterface;

/**
 * Pre-apply AI Application Assistant.
 */
final class ApplicationAssistantService
{
    public function __construct(
        private readonly JobRepositoryInterface $jobs,
        private readonly ResumeRepositoryInterface $resumes,
        private readonly ApplicationAssistantAnalysisRepositoryInterface $analyses,
        private readonly ApplicationAssistantHistoryRepositoryInterface $history,
        private readonly ApplicationReadinessAnalyzer $analyzer,
        private readonly ApplicationAssistantPdfExporter $pdfExporter,
        private readonly JobMatchContextFactory $matchContexts,
        private readonly JobMatchScoringService $matchScoring,
        private readonly ResumeJobMatchRepositoryInterface $matchSnapshots,
        private readonly ResumeIntelligenceRepositoryInterface $intelligence,
        private readonly ResumeSkillRepositoryInterface $skills,
        private readonly ResumeProfessionalRepositoryInterface $professional,
        private readonly WorkExperienceRepositoryInterface $experience,
        private readonly ResumeProjectRepositoryInterface $projects,
        private readonly ResumePortfolioRepositoryInterface $portfolio,
        private readonly ResumeAchievementRepositoryInterface $achievements,
        private readonly ApplicationAssistantPolicy $policy,
        private readonly ApplicationAssistantValidator $validator,
    ) {
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    public function page(array $actor, int $jobId, ?int $resumeId = null): array
    {
        $this->validator->assertJobId($jobId);
        $this->assertCanUse($actor);
        $userId = (int) $actor['id'];
        $job = $this->requirePublishedJob($actor, $jobId);
        $resumes = $this->resumes->listActiveRecordsForUser($userId);

        if ($resumeId === null && $resumes !== []) {
            $resumeId = (int) ($resumes[0]['id'] ?? 0);
        }

        $analysis = null;
        if ($resumeId !== null && $resumeId > 0) {
            $row = $this->analyses->findLatestForUserJob($userId, $jobId, $resumeId);
            if ($row !== null) {
                $analysis = ApplicationAnalysisDTO::fromRow($row);
            }
        }

        return [
            'job' => [
                'id' => (int) ($job['id'] ?? $jobId),
                'title' => (string) ($job['title'] ?? ''),
                'country_name' => (string) ($job['country_name'] ?? ''),
                'requirements' => (string) ($job['requirements'] ?? ''),
            ],
            'resumes' => $resumes,
            'selected_resume_id' => $resumeId,
            'analysis' => $analysis,
            'versions' => $this->analyses->listByUserJob($userId, $jobId, 15),
            'history' => $this->history->listByUserJob($userId, $jobId, 20),
            'deleted_history' => $this->history->listDeletedByUserJob($userId, $jobId, 10),
            'version' => ApplicationAssistantVersion::CURRENT,
            'disclaimer' => 'Application Assistant estimates readiness before you apply using resume intelligence, job matching and portfolio signals. Deterministic heuristics only — not a hiring decision.',
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, analysis_id: int}
     */
    public function analyze(array $actor, int $jobId, int $resumeId): array
    {
        return $this->runAnalysis($actor, $jobId, $resumeId, 'analyze');
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, analysis_id: int}
     */
    public function recalculate(array $actor, int $jobId, int $resumeId): array
    {
        return $this->runAnalysis($actor, $jobId, $resumeId, 'recalculate');
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    public function historyPage(array $actor, int $jobId): array
    {
        $this->validator->assertJobId($jobId);
        $this->assertCanUse($actor);
        $job = $this->requirePublishedJob($actor, $jobId);
        $userId = (int) $actor['id'];

        return [
            'job' => [
                'id' => (int) ($job['id'] ?? $jobId),
                'title' => (string) ($job['title'] ?? ''),
            ],
            'history' => $this->history->listByUserJob($userId, $jobId, 50),
            'deleted_history' => $this->history->listDeletedByUserJob($userId, $jobId, 20),
            'versions' => $this->analyses->listByUserJob($userId, $jobId, 30),
            'version' => ApplicationAssistantVersion::CURRENT,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function softDeleteHistory(array $actor, int $jobId, int $historyId): array
    {
        $this->validator->assertJobId($jobId);
        $this->validator->assertHistoryId($historyId);
        $this->assertCanManageHistory($actor);
        $this->requirePublishedJob($actor, $jobId);
        $ok = $this->history->softDelete($historyId, (int) $actor['id'], $jobId);

        return [
            'success' => $ok,
            'message' => $ok ? 'History entry removed.' : 'History entry not found.',
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function restoreHistory(array $actor, int $jobId, int $historyId): array
    {
        $this->validator->assertJobId($jobId);
        $this->validator->assertHistoryId($historyId);
        $this->assertCanManageHistory($actor);
        $this->requirePublishedJob($actor, $jobId);
        $ok = $this->history->restore($historyId, (int) $actor['id'], $jobId);

        return [
            'success' => $ok,
            'message' => $ok ? 'History entry restored.' : 'Deleted history entry not found.',
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function purgeHistory(array $actor, int $jobId, int $historyId): array
    {
        $this->validator->assertJobId($jobId);
        $this->validator->assertHistoryId($historyId);
        $this->assertCanManageHistory($actor);
        $this->requirePublishedJob($actor, $jobId);
        $ok = $this->history->permanentDelete($historyId, (int) $actor['id'], $jobId);

        return [
            'success' => $ok,
            'message' => $ok ? 'History entry permanently deleted.' : 'History entry not found.',
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function clearHistory(array $actor, int $jobId): array
    {
        $this->validator->assertJobId($jobId);
        $this->assertCanManageHistory($actor);
        $this->requirePublishedJob($actor, $jobId);
        $n = $this->history->softDeleteAllForUserJob((int) $actor['id'], $jobId);

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
    public function exportPdf(array $actor, int $jobId, int $analysisId): array
    {
        $this->validator->assertJobId($jobId);
        $this->validator->assertAnalysisId($analysisId);
        $this->assertCanUse($actor);
        $this->requirePublishedJob($actor, $jobId);
        $row = $this->analyses->findOwned($analysisId, (int) $actor['id'], $jobId);
        if ($row === null) {
            throw ApplicationAssistantException::analysisNotFound();
        }
        $dto = ApplicationAnalysisDTO::fromRow($row);
        $content = $this->pdfExporter->export($dto);

        return [
            'filename' => 'application-assistant-' . $analysisId . '.pdf',
            'mime' => 'application/pdf',
            'content' => $content,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, analysis_id: int}
     */
    private function runAnalysis(array $actor, int $jobId, int $resumeId, string $action): array
    {
        $this->validator->assertJobId($jobId);
        $this->validator->assertResumeId($resumeId);
        $this->assertCanUse($actor);
        $userId = (int) $actor['id'];
        $job = $this->requirePublishedJob($actor, $jobId);

        $aggregate = $this->resumes->findAggregateById($resumeId);
        if ($aggregate === null || $aggregate->resume()->deletedAt() !== null) {
            throw ApplicationAssistantException::resumeNotFound();
        }
        if (!$this->policy->canAnalyzeResume($actor, $aggregate->resume())) {
            throw ApplicationAssistantException::forbidden();
        }

        $match = $this->matchScoring->score(
            $this->matchContexts->build($resumeId, $userId, $jobId)
        );
        // Persist match snapshot for reuse across modules
        $this->matchSnapshots->upsert($resumeId, $jobId, $match->toPersistPayload());

        $intel = $this->intelligence->findLatestByResumeId($resumeId);
        $pro = $this->professional->findByResumeId($resumeId) ?? [];

        $skillNames = [];
        foreach ($this->skills->listByResumeId($resumeId) as $row) {
            $n = trim((string) ($row['skill_name'] ?? $row['name'] ?? ''));
            if ($n !== '') {
                $skillNames[] = $n;
            }
        }

        $projectBlob = '';
        foreach ($this->projects->listByResumeId($resumeId) as $row) {
            $projectBlob .= ' ' . (string) ($row['title'] ?? '') . ' ' . (string) ($row['description'] ?? '');
        }
        $portfolioPack = $this->portfolio->listByResumeId($resumeId, [], 1, 50);
        foreach (($portfolioPack['items'] ?? []) as $row) {
            if (is_array($row)) {
                $projectBlob .= ' ' . (string) ($row['title'] ?? '') . ' ' . (string) ($row['description'] ?? '');
            }
        }

        $expBlob = '';
        foreach ($this->experience->listByResumeId($resumeId) as $row) {
            $expBlob .= ' ' . (string) ($row['job_title'] ?? '') . ' ' . (string) ($row['description'] ?? '')
                . ' ' . (string) ($row['achievements'] ?? '');
        }

        $reqKeywords = $this->extractKeywords((string) ($job['requirements'] ?? $job['description'] ?? ''));

        $ctx = [
            'resume_overall' => (int) ($intel['overall_score'] ?? 0),
            'project_count' => $this->projects->countActive($resumeId),
            'portfolio_count' => $this->portfolio->countActive($resumeId),
            'achievement_count' => $this->achievements->countActive($resumeId),
            'project_blob' => $projectBlob,
            'resume_text_blob' => implode(' ', $skillNames) . ' ' . (string) ($pro['summary'] ?? '')
                . ' ' . (string) ($pro['headline'] ?? '') . ' ' . $expBlob . ' ' . $projectBlob,
            'requirement_keywords' => $reqKeywords,
            'experience_min_years' => $job['experience_min_years'] ?? null,
            'resume_years' => $pro['years_of_experience'] ?? null,
        ];

        $dto = $this->analyzer->analyze(
            $userId,
            $jobId,
            $resumeId,
            (string) ($job['title'] ?? ''),
            $aggregate->resume()->title(),
            $match,
            $ctx,
        );

        $analysisId = $this->analyses->create($userId, $jobId, $resumeId, $dto->toPersistPayload());
        $row = $this->analyses->findOwned($analysisId, $userId, $jobId);
        $saved = $row !== null ? ApplicationAnalysisDTO::fromRow($row) : $dto;

        $this->history->append($userId, $jobId, $resumeId, [
            'analysis_id' => $analysisId,
            'action' => $action,
            'headline' => 'Readiness ' . $saved->readinessScore . '/100 · ' . $saved->resumeTitle,
            'readiness_score' => $saved->readinessScore,
            'rules_version' => ApplicationAssistantVersion::CURRENT,
            'snapshot_json' => $saved->toHistorySnapshot(),
        ]);

        return [
            'success' => true,
            'message' => 'Application readiness analyzed: ' . $saved->readinessScore . '/100.',
            'analysis_id' => $analysisId,
        ];
    }

    /**
     * @return list<string>
     */
    private function extractKeywords(string $text): array
    {
        $parts = preg_split('/[,;\n\r•|\-]+/u', $text) ?: [];
        $out = [];
        foreach ($parts as $part) {
            $part = trim(preg_replace('/\s+/u', ' ', $part) ?? '');
            if (mb_strlen($part) >= 3 && mb_strlen($part) <= 80) {
                $out[] = $part;
            }
            if (count($out) >= 10) {
                break;
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    private function requirePublishedJob(array $actor, int $jobId): array
    {
        $job = $this->jobs->findPublishedRecordById($jobId);
        if (!$this->policy->canAnalyzeJob($actor, $job)) {
            if ($job === null) {
                throw ApplicationAssistantException::jobNotFound();
            }
            throw ApplicationAssistantException::forbidden();
        }

        return $job;
    }

    /** @param array<string, mixed> $actor */
    private function assertCanUse(array $actor): void
    {
        if (!$this->policy->canUse($actor)) {
            throw ApplicationAssistantException::forbidden();
        }
    }

    /** @param array<string, mixed> $actor */
    private function assertCanManageHistory(array $actor): void
    {
        if (!$this->policy->canManageHistory($actor)) {
            throw ApplicationAssistantException::forbidden();
        }
    }
}

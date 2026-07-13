<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\SkillGap\Services;

use JobVisa\App\Domain\JobMatching\Services\JobMatchContextFactory;
use JobVisa\App\Domain\JobMatching\Services\JobMatchScoringService;
use JobVisa\App\Domain\Resume\Repositories\ResumeRepositoryInterface;
use JobVisa\App\Domain\Resume\Support\ResumeCompletionCalculator;
use JobVisa\App\Domain\SkillGap\DTO\SkillGapAnalysisDTO;
use JobVisa\App\Domain\SkillGap\Exceptions\SkillGapException;
use JobVisa\App\Domain\SkillGap\Policies\SkillGapPolicy;
use JobVisa\App\Domain\SkillGap\Support\SkillGapVersion;
use JobVisa\App\Domain\SkillGap\Validators\SkillGapValidator;
use JobVisa\App\Repositories\Contracts\JobRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeIntelligenceRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeJobMatchRepositoryInterface;
use JobVisa\App\Repositories\Contracts\SkillGapAnalysisRepositoryInterface;
use JobVisa\App\Repositories\Contracts\SkillGapHistoryRepositoryInterface;

/**
 * AI Skill Gap Analyzer application service.
 */
final class SkillGapService
{
    public function __construct(
        private readonly ResumeRepositoryInterface $resumes,
        private readonly SkillGapAnalysisRepositoryInterface $analyses,
        private readonly SkillGapHistoryRepositoryInterface $history,
        private readonly SkillGapAnalyzer $analyzer,
        private readonly SkillGapPdfExporter $pdfExporter,
        private readonly JobMatchContextFactory $matchContexts,
        private readonly JobMatchScoringService $matchScoring,
        private readonly ResumeJobMatchRepositoryInterface $matchSnapshots,
        private readonly JobRepositoryInterface $jobs,
        private readonly ResumeIntelligenceRepositoryInterface $intelligence,
        private readonly ResumeCompletionCalculator $completion,
        private readonly SkillGapPolicy $policy,
        private readonly SkillGapValidator $validator,
    ) {
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    public function page(array $actor, int $resumeId, ?int $jobId = null): array
    {
        $this->validator->assertResumeId($resumeId);
        $aggregate = $this->requireOwned($actor, $resumeId, 'view');
        $resume = $aggregate->resume();
        $userId = (int) $actor['id'];
        $matchedJobs = $this->matchSnapshots->listTopForResume($resumeId, 15);
        if ($matchedJobs === []) {
            foreach ($this->jobs->findPublished(20) as $job) {
                $matchedJobs[] = [
                    'job_id' => (int) ($job['id'] ?? 0),
                    'job_title' => (string) ($job['title'] ?? ''),
                    'overall_score' => 0,
                ];
            }
        }

        if ($jobId === null || $jobId < 1) {
            if ($matchedJobs !== []) {
                $jobId = (int) ($matchedJobs[0]['job_id'] ?? $matchedJobs[0]['id'] ?? 0);
            }
        }

        $analysis = null;
        if ($jobId !== null && $jobId > 0) {
            $row = $this->analyses->findLatestByResumeId($resumeId, $userId, $jobId);
            if ($row === null) {
                $row = $this->analyses->findLatestByResumeId($resumeId, $userId, null);
            }
            if ($row !== null) {
                $analysis = SkillGapAnalysisDTO::fromRow($row);
            }
        } else {
            $row = $this->analyses->findLatestByResumeId($resumeId, $userId, null);
            if ($row !== null) {
                $analysis = SkillGapAnalysisDTO::fromRow($row);
                $jobId = $analysis->jobId;
            }
        }

        return [
            'resume' => [
                'id' => $resume->id(),
                'title' => $resume->title(),
            ],
            'completion' => $this->completion->evaluate($userId, $resumeId),
            'matched_jobs' => $matchedJobs,
            'selected_job_id' => $jobId,
            'analysis' => $analysis,
            'versions' => $this->analyses->listByResumeId($resumeId, $userId, 12),
            'history' => $this->history->listByResumeId($resumeId, 20),
            'deleted_history' => $this->history->listDeletedByResumeId($resumeId, 10),
            'can_edit' => $this->policy->canAnalyze($actor, $resume),
            'version' => SkillGapVersion::CURRENT,
            'disclaimer' => 'Skill Gap Analyzer compares your resume to a target job using deterministic matching heuristics. Learning suggestions are guidance only — not accredited course endorsements.',
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
            'version' => SkillGapVersion::CURRENT,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, analysis_id: int}
     */
    public function analyze(array $actor, int $resumeId, int $jobId): array
    {
        return $this->runAnalysis($actor, $resumeId, $jobId, 'analyze');
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, analysis_id: int}
     */
    public function recalculate(array $actor, int $resumeId, int $jobId): array
    {
        return $this->runAnalysis($actor, $resumeId, $jobId, 'recalculate');
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
            throw SkillGapException::analysisNotFound();
        }
        $dto = SkillGapAnalysisDTO::fromRow($row);
        $content = $this->pdfExporter->export($dto);

        return [
            'filename' => 'skill-gap-' . $analysisId . '.pdf',
            'mime' => 'application/pdf',
            'content' => $content,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, analysis_id: int}
     */
    private function runAnalysis(array $actor, int $resumeId, int $jobId, string $action): array
    {
        $this->validator->assertResumeId($resumeId);
        $this->validator->assertJobId($jobId);
        $aggregate = $this->requireOwned($actor, $resumeId, 'analyze');
        $userId = (int) $actor['id'];

        $job = $this->jobs->findPublishedRecordById($jobId);
        if ($job === null) {
            throw SkillGapException::jobNotFound();
        }

        $match = $this->matchScoring->score(
            $this->matchContexts->build($resumeId, $userId, $jobId)
        );
        $this->matchSnapshots->upsert($resumeId, $jobId, $match->toPersistPayload());

        $intel = $this->intelligence->findLatestByResumeId($resumeId);
        $dto = $this->analyzer->analyze(
            $resumeId,
            $userId,
            $jobId,
            (string) ($job['title'] ?? $match->jobTitle),
            $match,
            [
                'resume_title' => $aggregate->resume()->title(),
                'resume_overall' => (int) ($intel['overall_score'] ?? 0),
            ],
        );

        $analysisId = $this->analyses->create($resumeId, $userId, $dto->toPersistPayload());
        $row = $this->analyses->findOwned($analysisId, $resumeId, $userId);
        $saved = $row !== null ? SkillGapAnalysisDTO::fromRow($row) : $dto;

        $this->history->append($resumeId, $userId, [
            'job_id' => $jobId,
            'analysis_id' => $analysisId,
            'action' => $action,
            'headline' => sprintf(
                'Gap %d%% · Readiness %d/100 · %s',
                $saved->gapPercentage,
                $saved->readinessScore,
                $saved->jobTitle
            ),
            'gap_percentage' => $saved->gapPercentage,
            'readiness_score' => $saved->readinessScore,
            'rules_version' => SkillGapVersion::CURRENT,
            'snapshot_json' => $saved->toHistorySnapshot(),
        ]);

        return [
            'success' => true,
            'message' => sprintf(
                'Skill gap analyzed: %d%% gap, readiness %d/100.',
                $saved->gapPercentage,
                $saved->readinessScore
            ),
            'analysis_id' => $analysisId,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     */
    private function requireOwned(array $actor, int $resumeId, string $mode): \JobVisa\App\Domain\Resume\Aggregates\ResumeAggregate
    {
        $aggregate = $this->resumes->findAggregateById($resumeId);
        if ($aggregate === null || $aggregate->resume()->deletedAt() !== null) {
            throw SkillGapException::resumeNotFound();
        }
        $resume = $aggregate->resume();
        $allowed = match ($mode) {
            'analyze' => $this->policy->canAnalyze($actor, $resume),
            'history' => $this->policy->canManageHistory($actor, $resume),
            'export' => $this->policy->canExport($actor, $resume),
            default => $this->policy->canView($actor, $resume),
        };
        if (!$allowed) {
            throw SkillGapException::forbidden();
        }

        return $aggregate;
    }
}

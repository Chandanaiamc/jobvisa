<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\MockInterview\Services;

use JobVisa\App\Domain\MockInterview\DTO\MockInterviewSessionDTO;
use JobVisa\App\Domain\MockInterview\Exceptions\MockInterviewException;
use JobVisa\App\Domain\MockInterview\Policies\MockInterviewPolicy;
use JobVisa\App\Domain\MockInterview\Support\MockInterviewVersion;
use JobVisa\App\Domain\MockInterview\Validators\MockInterviewValidator;
use JobVisa\App\Domain\Resume\Aggregates\ResumeAggregate;
use JobVisa\App\Domain\Resume\Repositories\ResumeRepositoryInterface;
use JobVisa\App\Domain\Resume\Support\ResumeCompletionCalculator;
use JobVisa\App\Repositories\Contracts\CareerCoachSessionRepositoryInterface;
use JobVisa\App\Repositories\Contracts\JobRepositoryInterface;
use JobVisa\App\Repositories\Contracts\MockInterviewHistoryRepositoryInterface;
use JobVisa\App\Repositories\Contracts\MockInterviewSessionRepositoryInterface;
use JobVisa\App\Repositories\Contracts\PortfolioBuilderPlanRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeIntelligenceRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeJobMatchRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeProfessionalRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeSkillRepositoryInterface;
use JobVisa\App\Repositories\Contracts\SkillGapAnalysisRepositoryInterface;

/**
 * AI Mock Interview Simulator application service.
 */
final class MockInterviewService
{
    public function __construct(
        private readonly ResumeRepositoryInterface $resumes,
        private readonly MockInterviewSessionRepositoryInterface $sessions,
        private readonly MockInterviewHistoryRepositoryInterface $history,
        private readonly MockInterviewAnalyzer $analyzer,
        private readonly MockInterviewPdfExporter $pdfExporter,
        private readonly SkillGapAnalysisRepositoryInterface $skillGaps,
        private readonly PortfolioBuilderPlanRepositoryInterface $portfolioPlans,
        private readonly CareerCoachSessionRepositoryInterface $coach,
        private readonly ResumeIntelligenceRepositoryInterface $intelligence,
        private readonly ResumeProfessionalRepositoryInterface $professional,
        private readonly ResumeSkillRepositoryInterface $skills,
        private readonly ResumeJobMatchRepositoryInterface $matches,
        private readonly JobRepositoryInterface $jobs,
        private readonly ResumeCompletionCalculator $completion,
        private readonly MockInterviewPolicy $policy,
        private readonly MockInterviewValidator $validator,
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

        $matchedJobs = $this->matches->listTopForResume($resumeId, 15);
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
            $latest = $this->sessions->findLatestByResumeId($resumeId, $userId);
            if ($latest !== null && isset($latest['job_id']) && (int) $latest['job_id'] > 0) {
                $jobId = (int) $latest['job_id'];
            } elseif ($matchedJobs !== []) {
                $jobId = (int) ($matchedJobs[0]['job_id'] ?? $matchedJobs[0]['id'] ?? 0);
            }
        }

        $row = $this->sessions->findLatestByResumeId($resumeId, $userId);
        $mockSession = $row !== null ? MockInterviewSessionDTO::fromRow($row) : null;

        return [
            'resume' => [
                'id' => $resume->id(),
                'title' => $resume->title(),
            ],
            'completion' => $this->completion->evaluate($userId, $resumeId),
            'matched_jobs' => $matchedJobs,
            'selected_job_id' => $jobId,
            'mock_session' => $mockSession,
            'versions' => $this->sessions->listByResumeId($resumeId, $userId, 12),
            'history' => $this->history->listByResumeId($resumeId, 20),
            'deleted_history' => $this->history->listDeletedByResumeId($resumeId, 10),
            'can_edit' => $this->policy->canGenerate($actor, $resume),
            'version' => MockInterviewVersion::CURRENT,
            'disclaimer' => 'Mock interview questions and scores are generated from your resume, target job and prior AI signals using deterministic rules. Practice guidance only — not a real interview or hiring decision.',
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
            'versions' => $this->sessions->listByResumeId($resumeId, (int) $actor['id'], 30),
            'version' => MockInterviewVersion::CURRENT,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, session_id: int}
     */
    public function generate(array $actor, int $resumeId, int $jobId): array
    {
        return $this->runGenerate($actor, $resumeId, $jobId, 'generate');
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, session_id: int}
     */
    public function recalculate(array $actor, int $resumeId, int $jobId): array
    {
        return $this->runGenerate($actor, $resumeId, $jobId, 'recalculate');
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $answers
     * @return array{success: bool, message: string, session_id: int}
     */
    public function analyze(array $actor, int $resumeId, int $sessionId, array $answers): array
    {
        $this->validator->assertResumeId($resumeId);
        $this->validator->assertSessionId($sessionId);
        $this->requireOwned($actor, $resumeId, 'generate');
        $userId = (int) $actor['id'];
        $normalized = $this->validator->normalizeAnswers($answers);

        $row = $this->sessions->findOwned($sessionId, $resumeId, $userId);
        if ($row === null) {
            throw MockInterviewException::sessionNotFound();
        }
        $dto = MockInterviewSessionDTO::fromRow($row);
        $context = $this->buildContext($resumeId, $userId, $dto->jobId ?? 0);
        $scored = $this->analyzer->analyze($dto->session, $normalized, $context);

        $payload = [
            'job_id' => $dto->jobId,
            'job_title' => $dto->jobTitle,
            'career_level' => $dto->careerLevel,
            'status' => MockInterviewVersion::STATUS_ANALYZED,
            'overall_score' => $scored['overall'],
            'communication_score' => $scored['communication'],
            'technical_score' => $scored['technical'],
            'confidence_score' => $scored['confidence'],
            'star_score' => $scored['star'],
            'session_json' => $scored['session_json'],
            'rules_version' => MockInterviewVersion::CURRENT,
        ];
        $ok = $this->sessions->update($sessionId, $resumeId, $userId, $payload);
        if (!$ok) {
            throw MockInterviewException::sessionNotFound();
        }

        $savedRow = $this->sessions->findOwned($sessionId, $resumeId, $userId);
        $saved = $savedRow !== null ? MockInterviewSessionDTO::fromRow($savedRow) : $dto;

        $this->history->append($resumeId, $userId, [
            'session_id' => $sessionId,
            'action' => 'analyze',
            'headline' => sprintf(
                'Analyzed %s · overall %d/100 · %s',
                $saved->jobTitle,
                $saved->overallScore,
                $saved->careerLevel
            ),
            'overall_score' => $saved->overallScore,
            'rules_version' => MockInterviewVersion::CURRENT,
            'snapshot_json' => $saved->toHistorySnapshot(),
        ]);

        return [
            'success' => true,
            'message' => sprintf('Interview analyzed: overall %d/100.', $saved->overallScore),
            'session_id' => $sessionId,
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
    public function exportPdf(array $actor, int $resumeId, int $sessionId): array
    {
        $this->validator->assertResumeId($resumeId);
        $this->validator->assertSessionId($sessionId);
        $this->requireOwned($actor, $resumeId, 'export');
        $row = $this->sessions->findOwned($sessionId, $resumeId, (int) $actor['id']);
        if ($row === null) {
            throw MockInterviewException::sessionNotFound();
        }
        $dto = MockInterviewSessionDTO::fromRow($row);
        $content = $this->pdfExporter->export($dto);

        return [
            'filename' => 'mock-interview-' . $sessionId . '.pdf',
            'mime' => 'application/pdf',
            'content' => $content,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, session_id: int}
     */
    private function runGenerate(array $actor, int $resumeId, int $jobId, string $action): array
    {
        $this->validator->assertResumeId($resumeId);
        $this->validator->assertJobId($jobId);
        $this->requireOwned($actor, $resumeId, 'generate');
        $userId = (int) $actor['id'];
        $context = $this->buildContext($resumeId, $userId, $jobId);
        $dto = $this->analyzer->generate($resumeId, $userId, $context);
        $sessionId = $this->sessions->create($resumeId, $userId, $dto->toPersistPayload());
        $row = $this->sessions->findOwned($sessionId, $resumeId, $userId);
        $saved = $row !== null ? MockInterviewSessionDTO::fromRow($row) : $dto;

        $qCount = is_array($saved->session['questions'] ?? null) ? count($saved->session['questions']) : 0;
        $this->history->append($resumeId, $userId, [
            'session_id' => $sessionId,
            'action' => $action,
            'headline' => sprintf(
                '%s · %d questions · %s',
                $saved->jobTitle,
                $qCount,
                $saved->careerLevel
            ),
            'overall_score' => $saved->overallScore,
            'rules_version' => MockInterviewVersion::CURRENT,
            'snapshot_json' => $saved->toHistorySnapshot(),
        ]);

        return [
            'success' => true,
            'message' => sprintf(
                'Mock interview ready: %d questions for %s (%s).',
                $qCount,
                $saved->jobTitle,
                $saved->careerLevel
            ),
            'session_id' => $sessionId,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildContext(int $resumeId, int $userId, int $jobId): array
    {
        $job = $this->jobs->findPublishedRecordById($jobId);
        if ($job === null && $jobId > 0) {
            throw MockInterviewException::jobNotFound();
        }

        $gap = $this->skillGaps->findLatestByResumeId($resumeId, $userId, $jobId > 0 ? $jobId : null);
        if ($gap === null) {
            $gap = $this->skillGaps->findLatestByResumeId($resumeId, $userId, null);
        }
        $plan = $this->portfolioPlans->findLatestByResumeId($resumeId, $userId);
        $coach = $this->coach->findByResumeId($resumeId);
        $intel = $this->intelligence->findLatestByResumeId($resumeId);
        $pro = $this->professional->findByResumeId($resumeId) ?? [];
        $topMatch = $this->matches->listTopForResume($resumeId, 1);

        $jobTitle = trim((string) ($job['title'] ?? ''));
        if ($jobTitle === '') {
            $jobTitle = trim((string) ($gap['job_title'] ?? $topMatch[0]['job_title'] ?? $coach['target_role'] ?? $pro['current_job_title'] ?? 'Target Role'));
        }
        if ($jobTitle === '') {
            $jobTitle = 'Target Role';
        }

        $resolvedJobId = $jobId > 0
            ? $jobId
            : (isset($gap['job_id']) ? (int) $gap['job_id'] : (isset($topMatch[0]['job_id']) ? (int) $topMatch[0]['job_id'] : null));

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

        return [
            'job_id' => $resolvedJobId,
            'job_title' => $jobTitle,
            'years' => $years,
            'skills' => $skillNames,
            'missing_skills' => $comparison['missing_skills'] ?? [],
            'matched_skills' => $comparison['matched_skills'] ?? [],
            'gap_percentage' => (int) ($gap['gap_percentage'] ?? 0),
            'resume_overall' => (int) ($intel['overall_score'] ?? 0),
            'portfolio_strength' => (int) ($plan['strength_score'] ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     */
    private function requireOwned(array $actor, int $resumeId, string $mode): ResumeAggregate
    {
        $aggregate = $this->resumes->findAggregateById($resumeId);
        if ($aggregate === null || $aggregate->resume()->deletedAt() !== null) {
            throw MockInterviewException::resumeNotFound();
        }
        $resume = $aggregate->resume();
        $allowed = match ($mode) {
            'generate' => $this->policy->canGenerate($actor, $resume),
            'history' => $this->policy->canManageHistory($actor, $resume),
            'export' => $this->policy->canExport($actor, $resume),
            default => $this->policy->canView($actor, $resume),
        };
        if (!$allowed) {
            throw MockInterviewException::forbidden();
        }

        return $aggregate;
    }
}

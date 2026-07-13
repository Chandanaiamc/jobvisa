<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\CoverLetter\Services;

use JobVisa\App\Domain\CoverLetter\DTO\CoverLetterVersionDTO;
use JobVisa\App\Domain\CoverLetter\Exceptions\CoverLetterException;
use JobVisa\App\Domain\CoverLetter\Policies\CoverLetterPolicy;
use JobVisa\App\Domain\CoverLetter\Support\CoverLetterRulesVersion;
use JobVisa\App\Domain\CoverLetter\Validators\CoverLetterValidator;
use JobVisa\App\Domain\Resume\Aggregates\ResumeAggregate;
use JobVisa\App\Domain\Resume\Repositories\ResumeRepositoryInterface;
use JobVisa\App\Domain\Resume\Support\ResumeCompletionCalculator;
use JobVisa\App\Repositories\Contracts\CareerCoachSessionRepositoryInterface;
use JobVisa\App\Repositories\Contracts\CoverLetterHistoryRepositoryInterface;
use JobVisa\App\Repositories\Contracts\CoverLetterVersionRepositoryInterface;
use JobVisa\App\Repositories\Contracts\JobRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeAchievementRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeIntelligenceRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeJobMatchRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeSkillRepositoryInterface;
use JobVisa\App\Repositories\Contracts\UserProfileRepositoryInterface;

/**
 * Jobseeker AI Cover Letter Generator application service.
 */
final class CoverLetterService
{
    public function __construct(
        private readonly ResumeRepositoryInterface $resumes,
        private readonly CoverLetterVersionRepositoryInterface $versions,
        private readonly CoverLetterHistoryRepositoryInterface $history,
        private readonly CoverLetterGenerator $generator,
        private readonly CoverLetterPdfExporter $pdfExporter,
        private readonly CoverLetterDocxExporter $docxExporter,
        private readonly CoverLetterPolicy $policy,
        private readonly CoverLetterValidator $validator,
        private readonly ResumeCompletionCalculator $completion,
        private readonly JobRepositoryInterface $jobs,
        private readonly ResumeIntelligenceRepositoryInterface $intelligence,
        private readonly ResumeJobMatchRepositoryInterface $matches,
        private readonly CareerCoachSessionRepositoryInterface $coachSessions,
        private readonly ResumeSkillRepositoryInterface $skills,
        private readonly ResumeAchievementRepositoryInterface $achievements,
        private readonly UserProfileRepositoryInterface $profiles,
    ) {
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    public function page(array $actor, int $resumeId, ?int $previewVersionId = null): array
    {
        $this->validator->assertResumeId($resumeId);
        $aggregate = $this->requireResume($actor, $resumeId, true);
        $canEdit = $this->policy->canGenerate($actor, $aggregate->resume());
        $userId = $aggregate->resume()->userId();
        $completion = $this->completion->evaluate($userId, $resumeId);

        $versionRows = $this->versions->listByResumeId($resumeId, 20);
        $preview = null;
        if ($previewVersionId !== null && $previewVersionId > 0) {
            $row = $this->versions->findOwned($previewVersionId, $resumeId);
            if ($row !== null) {
                $preview = CoverLetterVersionDTO::fromRow($row, $canEdit);
            }
        }
        if ($preview === null && $versionRows !== []) {
            $preview = CoverLetterVersionDTO::fromRow($versionRows[0], $canEdit);
        }

        $matchedJobs = $this->matches->listTopForResume($resumeId, 10);

        return [
            'resume' => [
                'id' => $resumeId,
                'title' => $aggregate->resume()->title(),
                'status' => $aggregate->resume()->status(),
                'completeness_score' => $completion['score'],
            ],
            'completion' => $completion,
            'preview' => $preview,
            'versions' => array_map(
                static fn (array $row): CoverLetterVersionDTO => CoverLetterVersionDTO::fromRow($row, $canEdit),
                $versionRows
            ),
            'matched_jobs' => $matchedJobs,
            'styles' => CoverLetterRulesVersion::styles(),
            'history' => $canEdit ? $this->history->listByResumeId($resumeId, 20) : [],
            'deleted_history' => $canEdit ? $this->history->listDeletedByResumeId($resumeId, 10) : [],
            'can_edit' => $canEdit,
            'version' => CoverLetterRulesVersion::CURRENT,
            'disclaimer' => 'Cover letters are generated with deterministic rules from your resume, intelligence scores, career coach signals and job-match data. Preview before saving. No external AI APIs.',
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, version_id: int}
     */
    public function generate(
        array $actor,
        int $resumeId,
        ?int $jobId,
        ?string $style,
        ?string $tone = null,
        ?string $label = null,
    ): array {
        $this->validator->assertResumeId($resumeId);
        $aggregate = $this->requireResume($actor, $resumeId, false);
        $userId = $aggregate->resume()->userId();
        $normalizedStyle = $this->validator->normalizeStyle($style);
        $normalizedTone = $this->validator->normalizeTone($tone);
        $versionLabel = $this->validator->normalizeLabel($label, $normalizedStyle);
        $jobId = $this->validator->normalizeJobId($jobId);

        $ctx = $this->buildContext($actor, $resumeId, $userId, $jobId, $normalizedStyle, $normalizedTone);
        $pack = $this->generator->generate($ctx);

        $versionId = $this->versions->create($resumeId, $userId, [
            'job_id' => $jobId,
            'version_label' => $versionLabel,
            'status' => CoverLetterRulesVersion::STATUS_PREVIEW,
            'style' => $normalizedStyle,
            'tone' => $normalizedTone,
            'body_text' => $pack['body_text'],
            'highlights_json' => $pack['highlights'],
            'context_json' => $pack['context'],
            'ats_score' => $pack['ats_score'],
            'rules_version' => CoverLetterRulesVersion::CURRENT,
            'is_active' => 0,
        ]);

        $row = $this->versions->findOwned($versionId, $resumeId);
        $dto = $row !== null ? CoverLetterVersionDTO::fromRow($row, true) : null;

        $this->history->append($resumeId, $userId, [
            'version_id' => $versionId,
            'action' => 'generate',
            'style' => $normalizedStyle,
            'headline' => $versionLabel . ' · ATS ' . $pack['ats_score'],
            'ats_score' => $pack['ats_score'],
            'rules_version' => CoverLetterRulesVersion::CURRENT,
            'snapshot_json' => $dto?->toHistorySnapshot() ?? $pack,
        ]);

        return [
            'success' => true,
            'message' => 'Cover letter preview ready (ATS ' . $pack['ats_score'] . '/100). Review before saving.',
            'version_id' => $versionId,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, version_id: int}
     */
    public function regenerate(
        array $actor,
        int $resumeId,
        ?int $jobId,
        ?string $style,
        ?string $tone = null,
    ): array {
        $result = $this->generate(
            $actor,
            $resumeId,
            $jobId,
            $style,
            $tone,
            'Regenerated ' . ($style ?: 'professional') . ' ' . date('Y-m-d H:i')
        );

        return [
            'success' => true,
            'message' => 'Cover letter regenerated with the selected style/tone.',
            'version_id' => $result['version_id'],
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function saveVersion(array $actor, int $resumeId, int $versionId): array
    {
        $this->validator->assertResumeId($resumeId);
        $this->validator->assertVersionId($versionId);
        $aggregate = $this->requireResume($actor, $resumeId, false);
        $row = $this->versions->findOwned($versionId, $resumeId);
        if ($row === null) {
            throw CoverLetterException::versionNotFound();
        }
        $ok = $this->versions->markSaved($versionId, $resumeId);
        if ($ok) {
            $this->history->append($resumeId, $aggregate->resume()->userId(), [
                'version_id' => $versionId,
                'action' => 'save',
                'style' => (string) ($row['style'] ?? ''),
                'headline' => 'Saved: ' . (string) ($row['version_label'] ?? 'version'),
                'ats_score' => (int) ($row['ats_score'] ?? 0),
                'rules_version' => CoverLetterRulesVersion::CURRENT,
                'snapshot_json' => CoverLetterVersionDTO::fromRow($row, true)->toHistorySnapshot(),
            ]);
        }

        return [
            'success' => $ok,
            'message' => $ok ? 'Cover letter version saved.' : 'Unable to save version.',
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function softDeleteVersion(array $actor, int $resumeId, int $versionId): array
    {
        $this->validator->assertResumeId($resumeId);
        $this->validator->assertVersionId($versionId);
        $this->requireResume($actor, $resumeId, false);
        $ok = $this->versions->softDelete($versionId, $resumeId);

        return [
            'success' => $ok,
            'message' => $ok ? 'Cover letter version removed.' : 'Version not found.',
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function softDeleteHistoryEntry(array $actor, int $resumeId, int $historyId): array
    {
        $this->validator->assertResumeId($resumeId);
        $this->validator->assertHistoryId($historyId);
        $aggregate = $this->requireResume($actor, $resumeId, false);
        if (!$this->policy->canManageHistory($actor, $aggregate->resume())) {
            throw CoverLetterException::forbidden();
        }
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
    public function restoreHistoryEntry(array $actor, int $resumeId, int $historyId): array
    {
        $this->validator->assertResumeId($resumeId);
        $this->validator->assertHistoryId($historyId);
        $aggregate = $this->requireResume($actor, $resumeId, false);
        if (!$this->policy->canManageHistory($actor, $aggregate->resume())) {
            throw CoverLetterException::forbidden();
        }
        $ok = $this->history->restore($historyId, $resumeId);

        return [
            'success' => $ok,
            'message' => $ok ? 'History entry restored.' : 'Deleted history entry not found.',
        ];
    }

    /**
     * Permanent hard delete of a history row.
     *
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function permanentDeleteHistoryEntry(array $actor, int $resumeId, int $historyId): array
    {
        $this->validator->assertResumeId($resumeId);
        $this->validator->assertHistoryId($historyId);
        $aggregate = $this->requireResume($actor, $resumeId, false);
        if (!$this->policy->canManageHistory($actor, $aggregate->resume())) {
            throw CoverLetterException::forbidden();
        }
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
        $aggregate = $this->requireResume($actor, $resumeId, false);
        if (!$this->policy->canManageHistory($actor, $aggregate->resume())) {
            throw CoverLetterException::forbidden();
        }
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
    public function exportPdf(array $actor, int $resumeId, int $versionId): array
    {
        $dto = $this->requireExportable($actor, $resumeId, $versionId);
        $title = $dto->versionLabel;
        $content = $this->pdfExporter->export($title, $dto->bodyText);

        return [
            'filename' => 'cover-letter-' . $versionId . '.pdf',
            'mime' => 'application/pdf',
            'content' => $content,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{filename: string, mime: string, content: string}
     */
    public function exportDocx(array $actor, int $resumeId, int $versionId): array
    {
        $dto = $this->requireExportable($actor, $resumeId, $versionId);
        $content = $this->docxExporter->export($dto->versionLabel, $dto->bodyText);

        return [
            'filename' => 'cover-letter-' . $versionId . '.docx',
            'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'content' => $content,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    private function buildContext(
        array $actor,
        int $resumeId,
        int $userId,
        ?int $jobId,
        string $style,
        ?string $tone,
    ): array {
        $candidate = (string) ($actor['full_name'] ?? '');
        if ($candidate === '') {
            $profile = $this->profiles->findByUserId($userId) ?? [];
            $candidate = trim((string) ($profile['full_name'] ?? 'Applicant'));
        }

        $intel = $this->intelligence->findLatestByResumeId($resumeId);
        $coach = $this->coachSessions->findByResumeId($resumeId);
        $coachSummary = [];
        if ($coach !== null) {
            $raw = $coach['summary_json'] ?? [];
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                $coachSummary = is_array($decoded) ? $decoded : [];
            } elseif (is_array($raw)) {
                $coachSummary = $raw;
            }
        }

        $jobTitle = 'the open role';
        $requirements = '';
        $companyHint = 'your organisation';
        $matchOverall = 0;
        $matchedSkills = [];

        if ($jobId !== null) {
            $job = $this->jobs->findPublishedRecordById($jobId);
            if ($job === null) {
                throw CoverLetterException::jobNotFound();
            }
            $jobTitle = (string) ($job['title'] ?? $jobTitle);
            $requirements = (string) ($job['requirements'] ?? $job['description'] ?? '');
            $companyHint = (string) ($job['country_name'] ?? 'your organisation');
            $snap = $this->matches->findByResumeAndJob($resumeId, $jobId);
            if ($snap !== null) {
                $matchOverall = (int) ($snap['overall_score'] ?? 0);
                $explanation = $snap['explanation_json'] ?? [];
                if (is_string($explanation)) {
                    $explanation = json_decode($explanation, true) ?: [];
                }
                if (is_array($explanation)) {
                    foreach (($explanation['matched_requirements'] ?? []) as $item) {
                        if (is_string($item) && trim($item) !== '') {
                            $matchedSkills[] = trim($item);
                        }
                    }
                }
            }
        } else {
            $top = $this->matches->listTopForResume($resumeId, 1);
            if ($top !== []) {
                $jobTitle = (string) ($top[0]['job_title'] ?? $jobTitle);
                $matchOverall = (int) ($top[0]['overall_score'] ?? 0);
                $jobIdFromMatch = (int) ($top[0]['job_id'] ?? 0);
                if ($jobIdFromMatch > 0) {
                    $job = $this->jobs->findPublishedRecordById($jobIdFromMatch);
                    if ($job !== null) {
                        $requirements = (string) ($job['requirements'] ?? $job['description'] ?? '');
                    }
                }
            }
        }

        if ($matchedSkills === []) {
            foreach ($this->skills->listByResumeId($resumeId) as $row) {
                $name = trim((string) ($row['skill_name'] ?? $row['name'] ?? ''));
                if ($name !== '') {
                    $matchedSkills[] = $name;
                }
            }
        }

        $achievementLines = [];
        foreach ($this->achievements->listByResumeId($resumeId) as $row) {
            $title = trim((string) ($row['title'] ?? $row['description'] ?? ''));
            if ($title !== '') {
                $achievementLines[] = $title;
            }
        }

        return [
            'style' => $style,
            'tone' => $tone,
            'candidate_name' => $candidate !== '' ? $candidate : 'Applicant',
            'job_title' => $jobTitle,
            'company_hint' => $companyHint,
            'job_requirements' => $requirements,
            'matched_skills' => array_values(array_unique($matchedSkills)),
            'achievements' => $achievementLines,
            'coach_focus' => (string) ($coachSummary['focus'] ?? $coachSummary['next_step'] ?? ''),
            'scores' => [
                'resume_overall' => (int) ($intel['overall_score'] ?? 0),
                'ats_score' => (int) ($intel['ats_score'] ?? 0),
                'employer_readiness' => (int) ($intel['employer_readiness_score'] ?? 0),
                'match_overall' => $matchOverall,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     */
    private function requireExportable(array $actor, int $resumeId, int $versionId): CoverLetterVersionDTO
    {
        $this->validator->assertResumeId($resumeId);
        $this->validator->assertVersionId($versionId);
        $aggregate = $this->requireResume($actor, $resumeId, true);
        if (!$this->policy->canExport($actor, $aggregate->resume())) {
            throw CoverLetterException::forbidden();
        }
        $row = $this->versions->findOwned($versionId, $resumeId);
        if ($row === null) {
            throw CoverLetterException::versionNotFound();
        }

        return CoverLetterVersionDTO::fromRow($row, $this->policy->canGenerate($actor, $aggregate->resume()));
    }

    /**
     * @param  array<string, mixed>  $actor
     */
    private function requireResume(array $actor, int $resumeId, bool $viewOnly): ResumeAggregate
    {
        $aggregate = $this->resumes->findAggregateById($resumeId);
        if ($aggregate === null || $aggregate->resume()->deletedAt() !== null) {
            throw CoverLetterException::resumeNotFound();
        }
        $allowed = $viewOnly
            ? $this->policy->canView($actor, $aggregate->resume())
            : $this->policy->canGenerate($actor, $aggregate->resume());
        if (!$allowed) {
            throw CoverLetterException::forbidden();
        }

        return $aggregate;
    }
}

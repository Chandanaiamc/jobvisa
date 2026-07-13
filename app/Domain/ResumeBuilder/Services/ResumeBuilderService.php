<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\ResumeBuilder\Services;

use JobVisa\App\Domain\Resume\Aggregates\ResumeAggregate;
use JobVisa\App\Domain\Resume\Repositories\ResumeRepositoryInterface;
use JobVisa\App\Domain\Resume\Support\ResumeCompletionCalculator;
use JobVisa\App\Domain\ResumeBuilder\DTO\AiResumeVersionDTO;
use JobVisa\App\Domain\ResumeBuilder\Exceptions\ResumeBuilderException;
use JobVisa\App\Domain\ResumeBuilder\Policies\ResumeBuilderPolicy;
use JobVisa\App\Domain\ResumeBuilder\Support\ResumeBuilderVersion;
use JobVisa\App\Domain\ResumeBuilder\Validators\ResumeBuilderValidator;
use JobVisa\App\Repositories\Contracts\AiResumeBuilderHistoryRepositoryInterface;
use JobVisa\App\Repositories\Contracts\AiResumeVersionRepositoryInterface;
use JobVisa\App\Repositories\Contracts\EducationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeCertificationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeJobMatchRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeProfessionalRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeSkillRepositoryInterface;
use JobVisa\App\Repositories\Contracts\WorkExperienceRepositoryInterface;

/**
 * Jobseeker AI Resume Builder application service.
 */
final class ResumeBuilderService
{
    public function __construct(
        private readonly ResumeRepositoryInterface $resumes,
        private readonly AiResumeVersionRepositoryInterface $versions,
        private readonly AiResumeBuilderHistoryRepositoryInterface $history,
        private readonly ResumeBuilderGenerator $generator,
        private readonly ResumeBuilderPolicy $policy,
        private readonly ResumeBuilderValidator $validator,
        private readonly ResumeCompletionCalculator $completion,
        private readonly ResumeProfessionalRepositoryInterface $professional,
        private readonly ResumeSkillRepositoryInterface $skills,
        private readonly WorkExperienceRepositoryInterface $experience,
        private readonly EducationRepositoryInterface $education,
        private readonly ResumeCertificationRepositoryInterface $certifications,
        private readonly ResumeJobMatchRepositoryInterface $matches,
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
                $preview = AiResumeVersionDTO::fromRow($row, $canEdit);
            }
        }
        if ($preview === null && $versionRows !== []) {
            $preview = AiResumeVersionDTO::fromRow($versionRows[0], $canEdit);
        }

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
                static fn (array $row): AiResumeVersionDTO => AiResumeVersionDTO::fromRow($row, $canEdit),
                $versionRows
            ),
            'history' => $canEdit ? $this->history->listByResumeId($resumeId, 20) : [],
            'deleted_history' => $canEdit ? $this->history->listDeletedByResumeId($resumeId, 10) : [],
            'can_edit' => $canEdit,
            'version' => ResumeBuilderVersion::CURRENT,
            'disclaimer' => 'AI Resume Builder uses deterministic ATS heuristics from your resume and job-match keywords. It does not call external AI APIs. Preview content before saving a version.',
        ];
    }

    /**
     * Generate a new preview version (does not auto-save as active).
     *
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, version_id: int}
     */
    public function generate(array $actor, int $resumeId, ?string $targetRole = null, ?string $label = null): array
    {
        $this->validator->assertResumeId($resumeId);
        $aggregate = $this->requireResume($actor, $resumeId, false);
        $userId = $aggregate->resume()->userId();
        $role = $this->validator->normalizeTargetRole($targetRole);
        $versionLabel = $this->validator->normalizeLabel($label);

        $pack = $this->generator->generate($this->buildContext($resumeId, $role));
        $versionId = $this->versions->create($resumeId, $userId, [
            'version_label' => $versionLabel,
            'status' => ResumeBuilderVersion::STATUS_PREVIEW,
            'target_role' => $role,
            'professional_summary' => $pack['professional_summary'],
            'content_json' => $pack['content'],
            'ats_score' => $pack['ats_score'],
            'missing_keywords_json' => $pack['missing_keywords'],
            'keyword_suggestions_json' => $pack['keyword_suggestions'],
            'builder_version' => ResumeBuilderVersion::CURRENT,
            'is_active' => 0,
        ]);

        $row = $this->versions->findOwned($versionId, $resumeId);
        $dto = $row !== null ? AiResumeVersionDTO::fromRow($row, true) : null;

        $this->history->append($resumeId, $userId, [
            'version_id' => $versionId,
            'action' => 'generate',
            'headline' => $versionLabel . ' · ATS ' . $pack['ats_score'],
            'ats_score' => $pack['ats_score'],
            'builder_version' => ResumeBuilderVersion::CURRENT,
            'snapshot_json' => $dto?->toHistorySnapshot() ?? $pack,
        ]);

        return [
            'success' => true,
            'message' => 'Preview generated. Review ATS score ' . $pack['ats_score'] . '/100 before saving.',
            'version_id' => $versionId,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, version_id: int}
     */
    public function regenerate(array $actor, int $resumeId, ?string $targetRole = null): array
    {
        $result = $this->generate($actor, $resumeId, $targetRole, 'AI regenerate ' . date('Y-m-d H:i'));
        // Rewrite last history action label
        return [
            'success' => true,
            'message' => 'Resume content regenerated as a new preview.',
            'version_id' => $result['version_id'],
        ];
    }

    /**
     * Save a preview version (status → saved). Preview-before-save gate.
     *
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
            throw ResumeBuilderException::versionNotFound();
        }

        $ok = $this->versions->markSaved($versionId, $resumeId);
        if ($ok) {
            $this->history->append($resumeId, $aggregate->resume()->userId(), [
                'version_id' => $versionId,
                'action' => 'save',
                'headline' => 'Saved: ' . (string) ($row['version_label'] ?? 'version'),
                'ats_score' => (int) ($row['ats_score'] ?? 0),
                'builder_version' => ResumeBuilderVersion::CURRENT,
                'snapshot_json' => AiResumeVersionDTO::fromRow($row, true)->toHistorySnapshot(),
            ]);
        }

        return [
            'success' => $ok,
            'message' => $ok ? 'AI resume version saved.' : 'Unable to save version.',
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function activateVersion(array $actor, int $resumeId, int $versionId): array
    {
        $this->validator->assertResumeId($resumeId);
        $this->validator->assertVersionId($versionId);
        $aggregate = $this->requireResume($actor, $resumeId, false);

        $row = $this->versions->findOwned($versionId, $resumeId);
        if ($row === null) {
            throw ResumeBuilderException::versionNotFound();
        }
        if ((string) ($row['status'] ?? '') === ResumeBuilderVersion::STATUS_PREVIEW) {
            throw ResumeBuilderException::invalidAction('Save the preview before activating it.');
        }

        $ok = $this->versions->setActive($versionId, $resumeId);
        if ($ok) {
            $this->history->append($resumeId, $aggregate->resume()->userId(), [
                'version_id' => $versionId,
                'action' => 'activate',
                'headline' => 'Activated: ' . (string) ($row['version_label'] ?? 'version'),
                'ats_score' => (int) ($row['ats_score'] ?? 0),
                'builder_version' => ResumeBuilderVersion::CURRENT,
                'snapshot_json' => AiResumeVersionDTO::fromRow($row, true)->toHistorySnapshot(),
            ]);
        }

        return [
            'success' => $ok,
            'message' => $ok ? 'AI resume version set as active.' : 'Unable to activate version.',
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
            'message' => $ok ? 'AI resume version removed.' : 'Version not found.',
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
            throw ResumeBuilderException::forbidden();
        }
        $ok = $this->history->softDelete($historyId, $resumeId);

        return [
            'success' => $ok,
            'message' => $ok ? 'Generation history entry removed.' : 'History entry not found.',
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
            throw ResumeBuilderException::forbidden();
        }
        $ok = $this->history->restore($historyId, $resumeId);

        return [
            'success' => $ok,
            'message' => $ok ? 'Generation history entry restored.' : 'Deleted history entry not found.',
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
            throw ResumeBuilderException::forbidden();
        }
        $n = $this->history->softDeleteAllForResume($resumeId);

        return [
            'success' => true,
            'message' => $n > 0
                ? sprintf('Cleared %d generation history entries.', $n)
                : 'No generation history to clear.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildContext(int $resumeId, ?string $targetRole): array
    {
        $pro = $this->professional->findByResumeId($resumeId) ?? [];
        $skills = [];
        foreach ($this->skills->listByResumeId($resumeId) as $row) {
            $name = trim((string) ($row['skill_name'] ?? $row['name'] ?? ''));
            if ($name !== '') {
                $skills[] = $name;
            }
        }

        $matches = $this->matches->listTopForResume($resumeId, 8);
        $jobKeywords = [];
        $missingKeywords = [];
        foreach ($matches as $m) {
            $title = trim((string) ($m['job_title'] ?? ''));
            if ($title !== '') {
                $jobKeywords[] = $title;
            }
            $explanation = $m['explanation_json'] ?? null;
            if (is_string($explanation) && $explanation !== '') {
                $explanation = json_decode($explanation, true);
            }
            if (!is_array($explanation)) {
                continue;
            }
            foreach (($explanation['missing_required_skills'] ?? []) as $kw) {
                if (is_string($kw) && trim($kw) !== '') {
                    $missingKeywords[] = trim($kw);
                }
            }
            foreach (($explanation['matched_requirements'] ?? []) as $kw) {
                if (is_string($kw) && trim($kw) !== '') {
                    $jobKeywords[] = trim($kw);
                }
            }
        }

        $certs = [];
        foreach ($this->certifications->listByResumeId($resumeId) as $row) {
            $certs[] = $row;
        }

        return [
            'target_role' => $targetRole,
            'headline' => (string) ($pro['headline'] ?? ''),
            'summary' => (string) ($pro['summary'] ?? ''),
            'years_experience' => $pro['years_of_experience'] ?? null,
            'skills' => $skills,
            'experience' => $this->experience->listByResumeId($resumeId),
            'education' => $this->education->listByResumeId($resumeId),
            'certifications' => $certs,
            'job_keywords' => array_values(array_unique($jobKeywords)),
            'missing_keywords' => array_values(array_unique($missingKeywords)),
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     */
    private function requireResume(array $actor, int $resumeId, bool $viewOnly): ResumeAggregate
    {
        $aggregate = $this->resumes->findAggregateById($resumeId);
        if ($aggregate === null || $aggregate->resume()->deletedAt() !== null) {
            throw ResumeBuilderException::resumeNotFound();
        }

        $allowed = $viewOnly
            ? $this->policy->canView($actor, $aggregate->resume())
            : $this->policy->canGenerate($actor, $aggregate->resume());

        if (!$allowed) {
            throw ResumeBuilderException::forbidden();
        }

        return $aggregate;
    }
}

<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\CareerCoach\Services;

use JobVisa\App\Domain\CareerCoach\DTO\CareerCoachSessionDTO;
use JobVisa\App\Domain\CareerCoach\Exceptions\CareerCoachException;
use JobVisa\App\Domain\CareerCoach\Policies\CareerCoachPolicy;
use JobVisa\App\Domain\CareerCoach\Support\CareerCoachVersion;
use JobVisa\App\Domain\CareerCoach\Validators\CareerCoachValidator;
use JobVisa\App\Domain\Resume\Aggregates\ResumeAggregate;
use JobVisa\App\Domain\Resume\Repositories\ResumeRepositoryInterface;
use JobVisa\App\Domain\Resume\Support\ResumeCompletionCalculator;
use JobVisa\App\Repositories\Contracts\CareerCoachHistoryRepositoryInterface;
use JobVisa\App\Repositories\Contracts\CareerCoachSessionRepositoryInterface;
use JobVisa\App\Repositories\Contracts\EducationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeAchievementRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeCertificationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeIntelligenceRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeJobMatchRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumePortfolioRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeProfessionalRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeProjectRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeSkillRepositoryInterface;

/**
 * Jobseeker AI Career Coach application service.
 */
final class CareerCoachService
{
    public function __construct(
        private readonly ResumeRepositoryInterface $resumes,
        private readonly CareerCoachSessionRepositoryInterface $sessions,
        private readonly CareerCoachHistoryRepositoryInterface $history,
        private readonly CareerCoachGenerator $generator,
        private readonly CareerCoachPolicy $policy,
        private readonly CareerCoachValidator $validator,
        private readonly ResumeCompletionCalculator $completion,
        private readonly ResumeIntelligenceRepositoryInterface $intelligence,
        private readonly ResumeJobMatchRepositoryInterface $matches,
        private readonly ResumeSkillRepositoryInterface $skills,
        private readonly ResumeCertificationRepositoryInterface $certifications,
        private readonly ResumeProjectRepositoryInterface $projects,
        private readonly ResumeAchievementRepositoryInterface $achievements,
        private readonly ResumePortfolioRepositoryInterface $portfolio,
        private readonly EducationRepositoryInterface $education,
        private readonly ResumeProfessionalRepositoryInterface $professional,
    ) {
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    public function page(array $actor, int $resumeId, bool $forceRecalculate = false): array
    {
        $this->validator->assertResumeId($resumeId);
        $aggregate = $this->requireResume($actor, $resumeId, true);
        $canEdit = $this->policy->canRecalculate($actor, $aggregate->resume());
        $userId = $aggregate->resume()->userId();

        $coach = null;
        if (!$forceRecalculate) {
            $row = $this->sessions->findByResumeId($resumeId);
            if ($row !== null) {
                $coach = CareerCoachSessionDTO::fromRow($row, $canEdit);
            }
        }

        if ($coach === null) {
            $coach = $this->computeAndMaybePersist($resumeId, $userId, $canEdit, $canEdit, null);
        }

        $completion = $this->completion->evaluate($userId, $resumeId);

        return [
            'resume' => [
                'id' => $resumeId,
                'title' => $aggregate->resume()->title(),
                'status' => $aggregate->resume()->status(),
                'completeness_score' => $completion['score'],
            ],
            'completion' => $completion,
            'coach' => $coach,
            'history' => $canEdit ? $this->history->listByResumeId($resumeId, 20) : [],
            'deleted_history' => $canEdit ? $this->history->listDeletedByResumeId($resumeId, 10) : [],
            'can_edit' => $canEdit,
            'version' => CareerCoachVersion::CURRENT,
            'disclaimer' => 'Career Coach uses deterministic heuristics from your resume intelligence, skills, experience, education, certifications, projects, achievements and job-match data. It does not call external AI APIs and is not a hiring guarantee.',
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, coach?: CareerCoachSessionDTO}
     */
    public function recalculate(array $actor, int $resumeId, ?string $targetRole = null): array
    {
        $this->validator->assertResumeId($resumeId);
        $aggregate = $this->requireResume($actor, $resumeId, false);
        $role = $this->validator->normalizeTargetRole($targetRole);
        $dto = $this->computeAndMaybePersist(
            $resumeId,
            $aggregate->resume()->userId(),
            true,
            true,
            $role
        );

        return [
            'success' => true,
            'message' => 'Career coaching recommendations refreshed.',
            'coach' => $dto,
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
            throw CareerCoachException::forbidden();
        }

        $ok = $this->history->softDelete($historyId, $resumeId);

        return [
            'success' => $ok,
            'message' => $ok ? 'Coaching history entry removed.' : 'History entry not found.',
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
            throw CareerCoachException::forbidden();
        }

        $ok = $this->history->restore($historyId, $resumeId);

        return [
            'success' => $ok,
            'message' => $ok ? 'Coaching history entry restored.' : 'Deleted history entry not found.',
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
            throw CareerCoachException::forbidden();
        }

        $n = $this->history->softDeleteAllForResume($resumeId);

        return [
            'success' => true,
            'message' => $n > 0
                ? sprintf('Cleared %d coaching history entries.', $n)
                : 'No coaching history to clear.',
        ];
    }

    private function computeAndMaybePersist(
        int $resumeId,
        int $userId,
        bool $canEdit,
        bool $persist,
        ?string $targetRole,
    ): CareerCoachSessionDTO {
        $context = $this->buildContext($resumeId, $userId, $canEdit, $targetRole);
        $dto = $this->generator->generate($context);

        if ($persist) {
            $payload = $dto->toPersistPayload();
            $this->sessions->upsert($resumeId, $userId, $payload);
            $this->history->append($resumeId, $userId, [
                'target_role' => $dto->targetRole,
                'headline' => $dto->headline,
                'coach_version' => $dto->coachVersion,
                'snapshot_json' => $dto->toHistorySnapshot(),
            ]);
        }

        return $dto;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildContext(int $resumeId, int $userId, bool $canEdit, ?string $targetRole): array
    {
        $intel = $this->intelligence->findLatestByResumeId($resumeId);
        $analysis = [];
        if ($intel !== null && isset($intel['analysis_json'])) {
            $raw = $intel['analysis_json'];
            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);
                $analysis = is_array($decoded) ? $decoded : [];
            } elseif (is_array($raw)) {
                $analysis = $raw;
            }
        }

        $intelGaps = [];
        $gapsPayload = $analysis['skill_gaps'] ?? ($analysis['gaps'] ?? []);
        if (is_array($gapsPayload)) {
            if (isset($gapsPayload['missing']) && is_array($gapsPayload['missing'])) {
                foreach ($gapsPayload['missing'] as $item) {
                    if (is_string($item)) {
                        $intelGaps[] = ['skill' => $item, 'reason' => 'From resume intelligence skill-gap analysis.'];
                    } elseif (is_array($item)) {
                        $intelGaps[] = $item;
                    }
                }
            } else {
                foreach ($gapsPayload as $item) {
                    if (is_array($item)) {
                        $intelGaps[] = $item;
                    } elseif (is_string($item)) {
                        $intelGaps[] = ['skill' => $item];
                    }
                }
            }
        }

        $skillNames = [];
        foreach ($this->skills->listByResumeId($resumeId) as $row) {
            $name = trim((string) ($row['skill_name'] ?? $row['name'] ?? ''));
            if ($name !== '') {
                $skillNames[] = $name;
            }
        }

        $certNames = [];
        foreach ($this->certifications->listByResumeId($resumeId) as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name !== '') {
                $certNames[] = $name;
            }
        }

        $eduLabels = [];
        foreach ($this->education->listByResumeId($resumeId) as $row) {
            $label = trim((string) ($row['degree'] ?? '') . ' ' . (string) ($row['field_of_study'] ?? ''));
            if ($label !== '') {
                $eduLabels[] = $label;
            }
        }

        $matches = $this->matches->listTopForResume($resumeId, 10);
        $matchMissing = [];
        foreach ($matches as $m) {
            $explanation = $m['explanation_json'] ?? null;
            if (is_string($explanation) && $explanation !== '') {
                $explanation = json_decode($explanation, true);
            }
            if (!is_array($explanation)) {
                continue;
            }
            foreach (($explanation['missing_required_skills'] ?? []) as $skill) {
                if (is_string($skill) && trim($skill) !== '') {
                    $matchMissing[] = trim($skill);
                }
            }
        }

        $pro = $this->professional->findByResumeId($resumeId) ?? [];
        $years = isset($pro['years_of_experience']) && $pro['years_of_experience'] !== null
            ? (int) $pro['years_of_experience']
            : null;

        if ($targetRole === null && isset($intel['target_role']) && is_string($intel['target_role']) && $intel['target_role'] !== '') {
            $targetRole = (string) $intel['target_role'];
        }

        return [
            'resume_id' => $resumeId,
            'user_id' => $userId,
            'can_edit' => $canEdit,
            'target_role' => $targetRole,
            'skills' => $skillNames,
            'certifications' => $certNames,
            'education' => $eduLabels,
            'years_experience' => $years,
            'project_count' => $this->projects->countActive($resumeId),
            'achievement_count' => $this->achievements->countActive($resumeId),
            'portfolio_count' => $this->portfolio->countActive($resumeId),
            'job_matches' => $matches,
            'intelligence_gaps' => $intelGaps,
            'match_missing_skills' => array_values(array_unique($matchMissing)),
            'scores' => [
                'resume_overall' => (int) ($intel['overall_score'] ?? 0),
                'ats_score' => (int) ($intel['ats_score'] ?? 0),
                'employer_readiness' => (int) ($intel['employer_readiness_score'] ?? 0),
                'keyword_match' => (int) ($intel['keyword_match_score'] ?? 0),
                'top_match' => (int) (($matches[0]['overall_score'] ?? 0)),
                'match_count' => count($matches),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     */
    private function requireResume(array $actor, int $resumeId, bool $viewOnly): ResumeAggregate
    {
        $aggregate = $this->resumes->findAggregateById($resumeId);
        if ($aggregate === null || $aggregate->resume()->deletedAt() !== null) {
            throw CareerCoachException::resumeNotFound();
        }

        $allowed = $viewOnly
            ? $this->policy->canView($actor, $aggregate->resume())
            : $this->policy->canRecalculate($actor, $aggregate->resume());

        if (!$allowed) {
            throw CareerCoachException::forbidden();
        }

        return $aggregate;
    }
}

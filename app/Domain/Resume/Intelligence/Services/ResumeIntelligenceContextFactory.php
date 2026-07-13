<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Intelligence\Services;

use JobVisa\App\Domain\Resume\Intelligence\DTO\ResumeIntelligenceContext;
use JobVisa\App\Domain\Resume\Repositories\ResumeRepositoryInterface;
use JobVisa\App\Repositories\Contracts\EducationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeAchievementRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeCertificationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeLanguageRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumePortfolioRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeProfessionalRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeProjectRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumePublicationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeReferenceRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeRepositoryInterface as InfraResumeRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeSkillRepositoryInterface;
use JobVisa\App\Repositories\Contracts\UserProfileRepositoryInterface;
use JobVisa\App\Repositories\Contracts\WorkExperienceRepositoryInterface;

/**
 * Builds a privacy-safe scoring context (no protected characteristics / private contacts).
 */
final class ResumeIntelligenceContextFactory
{
    public function __construct(
        private readonly ResumeRepositoryInterface $domainResumes,
        private readonly InfraResumeRepositoryInterface $infraResumes,
        private readonly ResumeProfessionalRepositoryInterface $professional,
        private readonly UserProfileRepositoryInterface $profiles,
        private readonly EducationRepositoryInterface $education,
        private readonly WorkExperienceRepositoryInterface $experience,
        private readonly ResumeSkillRepositoryInterface $skills,
        private readonly ResumeLanguageRepositoryInterface $languages,
        private readonly ResumeCertificationRepositoryInterface $certifications,
        private readonly ResumeProjectRepositoryInterface $projects,
        private readonly ResumeAchievementRepositoryInterface $achievements,
        private readonly ResumePublicationRepositoryInterface $publications,
        private readonly ResumePortfolioRepositoryInterface $portfolios,
        private readonly ResumeReferenceRepositoryInterface $references,
    ) {
    }

    public function build(int $resumeId, int $userId): ResumeIntelligenceContext
    {
        $aggregate = $this->domainResumes->findAggregateById($resumeId);
        $resume = $aggregate?->resume();
        $record = $this->infraResumes->findByIdForUser($resumeId, $userId)
            ?? $this->infraResumes->findRecordById($resumeId)
            ?? [];

        $profile = $this->profiles->findByUserId($userId) ?? [];
        $profRow = $this->professional->findByResumeId($resumeId) ?? [];

        $first = trim((string) ($profile['first_name'] ?? ''));
        $last = trim((string) ($profile['last_name'] ?? ''));
        $phone = trim((string) ($profile['user_phone'] ?? ''));
        $email = trim((string) ($profile['user_email'] ?? $profile['email'] ?? ''));

        $headline = trim((string) ($profRow['headline'] ?? $profile['headline'] ?? ''));
        $summary = trim((string) ($profRow['summary'] ?? ''));

        $refs = $this->sanitizeReferences(
            $this->references->listByResumeId($resumeId, [], 1, 100)['items'] ?? []
        );

        return new ResumeIntelligenceContext(
            resumeId: $resumeId,
            userId: $userId,
            resumeTitle: $resume?->title() ?? (string) ($record['title'] ?? ''),
            hasCvFile: !empty($record['file_path']),
            hasDisplayName: $first !== '' && $last !== '',
            hasPhone: $phone !== '',
            hasEmail: $email !== '',
            hasLocation: !empty($profile['current_country_id']) || !empty($profile['current_city_id']),
            hasHeadline: $headline !== '',
            headline: $headline,
            summary: $summary,
            hasCareerObjective: trim((string) ($profRow['career_objective'] ?? '')) !== '',
            hasCurrentRole: trim((string) ($profRow['current_job_title'] ?? '')) !== ''
                || trim((string) ($profRow['current_company'] ?? '')) !== '',
            education: $this->education->listByResumeId($resumeId),
            experience: $this->experience->listByResumeId($resumeId),
            skills: $this->skills->listByResumeId($resumeId),
            languages: $this->languages->listByResumeId($resumeId),
            certifications: $this->certifications->listByResumeId($resumeId),
            projects: $this->projects->listByResumeId($resumeId),
            achievements: $this->achievements->listByResumeId($resumeId),
            publications: $this->publications->listByResumeId($resumeId, [], 1, 100)['items'] ?? [],
            portfolio: $this->portfolios->listByResumeId($resumeId, [], 1, 100)['items'] ?? [],
            references: $refs,
        );
    }

    /**
     * Strip reference contact fields so they never enter scoring memory/snapshots.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function sanitizeReferences(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id' => (int) ($row['id'] ?? 0),
                'name' => (string) ($row['name'] ?? ''),
                'company' => (string) ($row['company'] ?? ''),
                'relationship' => (string) ($row['relationship'] ?? ''),
                'visibility' => (string) ($row['visibility'] ?? 'private'),
                'permission_to_contact' => !empty($row['permission_to_contact']) ? 1 : 0,
                'status' => (string) ($row['status'] ?? 'active'),
            ];
        }

        return $out;
    }
}

<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\JobMatching\Services;

use JobVisa\App\Domain\JobMatching\DTO\JobMatchContext;
use JobVisa\App\Domain\JobMatching\Exceptions\JobMatchException;
use JobVisa\App\Domain\Resume\Repositories\ResumeRepositoryInterface as DomainResumeRepositoryInterface;
use JobVisa\App\Repositories\Contracts\EducationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\JobRepositoryInterface;
use JobVisa\App\Repositories\Contracts\LanguageCatalogRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeCertificationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeLanguageRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumePersonalRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeProfessionalRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeSkillRepositoryInterface;
use JobVisa\App\Repositories\Contracts\SkillCatalogRepositoryInterface;
use JobVisa\App\Repositories\Contracts\UserProfileRepositoryInterface;

/**
 * Builds privacy-safe match context from existing resume + job tables.
 */
final class JobMatchContextFactory
{
    public function __construct(
        private readonly DomainResumeRepositoryInterface $resumes,
        private readonly JobRepositoryInterface $jobs,
        private readonly ResumeSkillRepositoryInterface $skills,
        private readonly ResumeLanguageRepositoryInterface $languages,
        private readonly ResumeCertificationRepositoryInterface $certifications,
        private readonly EducationRepositoryInterface $education,
        private readonly ResumeProfessionalRepositoryInterface $professional,
        private readonly ResumePersonalRepositoryInterface $personal,
        private readonly UserProfileRepositoryInterface $profiles,
        private readonly SkillCatalogRepositoryInterface $skillCatalog,
        private readonly LanguageCatalogRepositoryInterface $languageCatalog,
    ) {
    }

    public function build(int $resumeId, int $userId, int $jobId): JobMatchContext
    {
        $aggregate = $this->resumes->findAggregateById($resumeId);
        if ($aggregate === null || $aggregate->resume()->deletedAt() !== null) {
            throw JobMatchException::resumeNotFound();
        }
        if ($aggregate->resume()->userId() !== $userId) {
            // Ownership enforced again in policy; keep context factory honest.
            throw JobMatchException::forbidden();
        }

        $job = $this->jobs->findPublishedRecordById($jobId);
        if ($job === null) {
            throw JobMatchException::jobNotFound();
        }

        $skillRows = [];
        foreach ($this->skills->listByResumeId($resumeId) as $row) {
            $skillRows[] = [
                'name' => (string) ($row['skill_name'] ?? $row['name'] ?? ''),
                'slug' => (string) ($row['skill_slug'] ?? ''),
                'years' => isset($row['years_experience']) ? (int) $row['years_experience'] : null,
                'is_primary' => !empty($row['is_primary']),
            ];
        }

        $langRows = [];
        foreach ($this->languages->listByResumeId($resumeId) as $row) {
            $langRows[] = [
                'name' => (string) ($row['language_name'] ?? ''),
                'code' => (string) ($row['language_code'] ?? ''),
            ];
        }

        $certs = [];
        foreach ($this->certifications->listByResumeId($resumeId) as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name !== '') {
                $certs[] = $name;
            }
        }

        $eduRows = [];
        foreach ($this->education->listByResumeId($resumeId) as $row) {
            $eduRows[] = [
                'degree' => (string) ($row['degree'] ?? ''),
                'qualification_type' => (string) ($row['qualification_type'] ?? ''),
                'field' => (string) ($row['field_of_study'] ?? ''),
            ];
        }

        $pro = $this->professional->findByResumeId($resumeId) ?? [];
        $profile = $this->profiles->findByUserId($userId) ?? [];
        $preferred = $this->personal->listPreferredCountryIds($resumeId);

        $catalogSkills = [];
        foreach ($this->skillCatalog->listActive() as $row) {
            $n = trim((string) ($row['name'] ?? ''));
            if ($n !== '') {
                $catalogSkills[] = $n;
            }
        }

        $catalogLangs = [];
        foreach ($this->languageCatalog->listActive() as $row) {
            $n = trim((string) ($row['name'] ?? ''));
            if ($n !== '') {
                $catalogLangs[] = $n;
            }
        }

        $years = isset($pro['years_of_experience']) && $pro['years_of_experience'] !== null && $pro['years_of_experience'] !== ''
            ? (int) $pro['years_of_experience']
            : null;

        return new JobMatchContext(
            resumeId: $resumeId,
            userId: $userId,
            jobId: $jobId,
            jobTitle: (string) ($job['title'] ?? ''),
            jobDescription: (string) ($job['description'] ?? ''),
            jobRequirements: (string) ($job['requirements'] ?? ''),
            experienceMinYears: isset($job['experience_min_years']) && $job['experience_min_years'] !== null
                ? (int) $job['experience_min_years']
                : null,
            educationLevel: isset($job['education_level']) && $job['education_level'] !== null && $job['education_level'] !== ''
                ? (string) $job['education_level']
                : null,
            jobCountryId: (int) ($job['country_id'] ?? 0),
            jobCityId: isset($job['city_id']) && $job['city_id'] !== null ? (int) $job['city_id'] : null,
            jobCountryName: (string) ($job['country_name'] ?? ''),
            jobTypeName: (string) ($job['job_type_name'] ?? ''),
            jobTypeSlug: (string) ($job['job_type_slug'] ?? ''),
            visaSponsorship: !empty($job['visa_sponsorship']),
            resumeSkills: $skillRows,
            resumeYearsExperience: $years,
            resumeLanguages: $langRows,
            resumeCertifications: $certs,
            resumeEducation: $eduRows,
            preferredCountryIds: array_map('intval', $preferred),
            profileCountryId: isset($profile['current_country_id']) && $profile['current_country_id'] !== null
                ? (int) $profile['current_country_id']
                : null,
            openToRelocate: !empty($pro['open_to_relocate']),
            openToRemote: !empty($pro['open_to_remote']),
            skillCatalogNames: $catalogSkills,
            languageCatalogNames: $catalogLangs,
        );
    }
}

<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\JobMatching\DTO;

/**
 * Privacy-safe matching context (no protected traits).
 */
final class JobMatchContext
{
    /**
     * @param  list<array{name: string, slug: string, years: int|null, is_primary: bool}>  $resumeSkills
     * @param  list<array{name: string, code: string}>  $resumeLanguages
     * @param  list<string>  $resumeCertifications
     * @param  list<array{degree: string, qualification_type: string, field: string}>  $resumeEducation
     * @param  list<int>  $preferredCountryIds
     * @param  list<string>  $skillCatalogNames
     * @param  list<string>  $languageCatalogNames
     */
    public function __construct(
        public readonly int $resumeId,
        public readonly int $userId,
        public readonly int $jobId,
        public readonly string $jobTitle,
        public readonly string $jobDescription,
        public readonly string $jobRequirements,
        public readonly ?int $experienceMinYears,
        public readonly ?string $educationLevel,
        public readonly int $jobCountryId,
        public readonly ?int $jobCityId,
        public readonly string $jobCountryName,
        public readonly string $jobTypeName,
        public readonly string $jobTypeSlug,
        public readonly bool $visaSponsorship,
        public readonly array $resumeSkills,
        public readonly ?int $resumeYearsExperience,
        public readonly array $resumeLanguages,
        public readonly array $resumeCertifications,
        public readonly array $resumeEducation,
        public readonly array $preferredCountryIds,
        public readonly ?int $profileCountryId,
        public readonly bool $openToRelocate,
        public readonly bool $openToRemote,
        public readonly array $skillCatalogNames,
        public readonly array $languageCatalogNames,
    ) {
    }
}

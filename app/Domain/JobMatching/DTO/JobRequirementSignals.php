<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\JobMatching\DTO;

/**
 * Signals extracted from job posting text + structured columns.
 */
final class JobRequirementSignals
{
    /**
     * @param  list<string>  $requiredSkills
     * @param  list<string>  $preferredSkills
     * @param  list<string>  $requiredLanguages
     * @param  list<string>  $preferredLanguages
     * @param  list<string>  $requiredCertifications
     * @param  list<string>  $preferredCertifications
     */
    public function __construct(
        public readonly array $requiredSkills,
        public readonly array $preferredSkills,
        public readonly array $requiredLanguages,
        public readonly array $preferredLanguages,
        public readonly array $requiredCertifications,
        public readonly array $preferredCertifications,
        public readonly bool $mentionsRemote,
        public readonly ?int $inferredMinExperience,
        public readonly ?string $inferredEducation,
    ) {
    }
}

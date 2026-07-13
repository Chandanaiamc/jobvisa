<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\InterviewAssistant\DTO;

/**
 * Context used to generate interview questions and insights.
 */
final class InterviewGenerationContext
{
    /**
     * @param  list<string>  $resumeSkills
     * @param  list<string>  $matchedSkills
     * @param  list<string>  $missingSkills
     * @param  list<string>  $requirementKeywords
     * @param  array<string, int>  $scores  resume/match/ranking dimensions
     */
    public function __construct(
        public readonly int $jobId,
        public readonly string $jobTitle,
        public readonly string $jobRequirements,
        public readonly string $jobDescription,
        public readonly ?int $experienceMinYears,
        public readonly ?string $educationLevel,
        public readonly int $applicationId,
        public readonly int $candidateUserId,
        public readonly string $candidateName,
        public readonly ?int $resumeId,
        public readonly array $resumeSkills,
        public readonly array $matchedSkills,
        public readonly array $missingSkills,
        public readonly array $requirementKeywords,
        public readonly array $scores,
        public readonly ?int $yearsExperience,
    ) {
    }
}

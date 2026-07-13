<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\RecruiterAssistant\DTO;

/**
 * Structured filters derived from natural language (deterministic).
 */
final class RecruiterSearchCriteria
{
    /**
     * @param  list<string>  $skills
     * @param  list<string>  $educationKeywords
     * @param  list<string>  $certifications
     * @param  list<string>  $interpreted
     */
    public function __construct(
        public readonly string $rawQuery,
        public readonly array $skills = [],
        public readonly ?int $minExperienceYears = null,
        public readonly array $educationKeywords = [],
        public readonly array $certifications = [],
        public readonly ?string $location = null,
        public readonly ?int $minMatchScore = null,
        public readonly ?int $minRankingScore = null,
        public readonly ?int $jobId = null,
        public readonly bool $interviewReadyOnly = false,
        public readonly array $interpreted = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'raw_query' => $this->rawQuery,
            'skills' => $this->skills,
            'min_experience_years' => $this->minExperienceYears,
            'education_keywords' => $this->educationKeywords,
            'certifications' => $this->certifications,
            'location' => $this->location,
            'min_match_score' => $this->minMatchScore,
            'min_ranking_score' => $this->minRankingScore,
            'job_id' => $this->jobId,
            'interview_ready_only' => $this->interviewReadyOnly,
            'interpreted' => $this->interpreted,
        ];
    }
}

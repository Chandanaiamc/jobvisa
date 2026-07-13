<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\ApplicationAssistant\DTO;

final class ApplicationAnalysisDTO
{
    /**
     * @param  array<string, mixed>  $analysis
     */
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly int $jobId,
        public readonly int $resumeId,
        public readonly string $jobTitle,
        public readonly string $resumeTitle,
        public readonly int $readinessScore,
        public readonly int $skillsScore,
        public readonly int $experienceScore,
        public readonly int $educationScore,
        public readonly int $certificationScore,
        public readonly int $portfolioScore,
        public readonly int $matchOverall,
        public readonly int $resumeOverall,
        public readonly array $analysis,
        public readonly string $rulesVersion,
        public readonly string $createdAt,
    ) {
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            (int) ($row['id'] ?? 0),
            (int) ($row['user_id'] ?? 0),
            (int) ($row['job_id'] ?? 0),
            (int) ($row['resume_id'] ?? 0),
            (string) ($row['job_title'] ?? ''),
            (string) ($row['resume_title'] ?? ''),
            max(0, min(100, (int) ($row['readiness_score'] ?? 0))),
            max(0, min(100, (int) ($row['skills_score'] ?? 0))),
            max(0, min(100, (int) ($row['experience_score'] ?? 0))),
            max(0, min(100, (int) ($row['education_score'] ?? 0))),
            max(0, min(100, (int) ($row['certification_score'] ?? 0))),
            max(0, min(100, (int) ($row['portfolio_score'] ?? 0))),
            max(0, min(100, (int) ($row['match_overall'] ?? 0))),
            max(0, min(100, (int) ($row['resume_overall'] ?? 0))),
            self::decodeMap($row['analysis_json'] ?? []),
            (string) ($row['rules_version'] ?? ''),
            (string) ($row['created_at'] ?? ''),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toPersistPayload(): array
    {
        return [
            'readiness_score' => $this->readinessScore,
            'skills_score' => $this->skillsScore,
            'experience_score' => $this->experienceScore,
            'education_score' => $this->educationScore,
            'certification_score' => $this->certificationScore,
            'portfolio_score' => $this->portfolioScore,
            'match_overall' => $this->matchOverall,
            'resume_overall' => $this->resumeOverall,
            'analysis_json' => $this->analysis,
            'rules_version' => $this->rulesVersion,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toHistorySnapshot(): array
    {
        return array_merge($this->toPersistPayload(), [
            'id' => $this->id,
            'user_id' => $this->userId,
            'job_id' => $this->jobId,
            'resume_id' => $this->resumeId,
            'job_title' => $this->jobTitle,
            'resume_title' => $this->resumeTitle,
            'created_at' => $this->createdAt,
        ]);
    }

    /** @return array<string, mixed> */
    private static function decodeMap(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (!is_string($value) || $value === '') {
            return [];
        }
        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}

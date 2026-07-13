<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\JobMatching\DTO;

/**
 * Deterministic resume↔job match result (0–100 scores).
 */
final class JobMatchResultDTO
{
    /**
     * @param  array<string, array{label: string, weight: int, earned: int, score: int, explain: string}>  $breakdown
     * @param  array<string, mixed>  $explanation
     * @param  list<MatchRecommendationDTO>  $recommendations
     */
    public function __construct(
        public readonly int $resumeId,
        public readonly int $jobId,
        public readonly string $jobTitle,
        public readonly int $overallScore,
        public readonly int $skillsScore,
        public readonly int $experienceScore,
        public readonly int $educationScore,
        public readonly int $languageScore,
        public readonly int $certificationScore,
        public readonly int $locationScore,
        public readonly array $breakdown,
        public readonly array $explanation,
        public readonly array $recommendations,
        public readonly string $rulesVersion,
        public readonly string $calculatedAt,
    ) {
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromSnapshotRow(array $row, string $jobTitle = ''): self
    {
        $breakdown = self::decodeJson($row['score_breakdown'] ?? null);
        $explanation = self::decodeJson($row['explanation_json'] ?? null);
        $recsRaw = self::decodeJson($row['recommendations'] ?? null);
        $recs = [];
        foreach ($recsRaw as $item) {
            if (is_array($item)) {
                $recs[] = MatchRecommendationDTO::fromArray($item);
            }
        }

        return new self(
            resumeId: (int) ($row['resume_id'] ?? 0),
            jobId: (int) ($row['job_id'] ?? 0),
            jobTitle: $jobTitle !== '' ? $jobTitle : (string) ($explanation['job_title'] ?? ''),
            overallScore: self::clamp((int) ($row['overall_score'] ?? 0)),
            skillsScore: self::clamp((int) ($row['skills_score'] ?? 0)),
            experienceScore: self::clamp((int) ($row['experience_score'] ?? 0)),
            educationScore: self::clamp((int) ($row['education_score'] ?? 0)),
            languageScore: self::clamp((int) ($row['language_score'] ?? 0)),
            certificationScore: self::clamp((int) ($row['certification_score'] ?? 0)),
            locationScore: self::clamp((int) ($row['location_score'] ?? 0)),
            breakdown: $breakdown,
            explanation: $explanation,
            recommendations: $recs,
            rulesVersion: (string) ($row['rules_version'] ?? ''),
            calculatedAt: (string) ($row['calculated_at'] ?? ''),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'resume_id' => $this->resumeId,
            'job_id' => $this->jobId,
            'job_title' => $this->jobTitle,
            'overall_score' => $this->overallScore,
            'skills_score' => $this->skillsScore,
            'experience_score' => $this->experienceScore,
            'education_score' => $this->educationScore,
            'language_score' => $this->languageScore,
            'certification_score' => $this->certificationScore,
            'location_score' => $this->locationScore,
            'score_breakdown' => $this->breakdown,
            'explanation' => $this->explanation,
            'recommendations' => array_map(
                static fn (MatchRecommendationDTO $r): array => $r->toArray(),
                $this->recommendations
            ),
            'rules_version' => $this->rulesVersion,
            'calculated_at' => $this->calculatedAt,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toPersistPayload(): array
    {
        return [
            'overall_score' => $this->overallScore,
            'skills_score' => $this->skillsScore,
            'experience_score' => $this->experienceScore,
            'education_score' => $this->educationScore,
            'language_score' => $this->languageScore,
            'certification_score' => $this->certificationScore,
            'location_score' => $this->locationScore,
            'score_breakdown' => $this->breakdown,
            'explanation_json' => $this->explanation,
            'recommendations' => array_map(
                static fn (MatchRecommendationDTO $r): array => $r->toArray(),
                $this->recommendations
            ),
            'rules_version' => $this->rulesVersion,
            'calculated_at' => $this->calculatedAt,
        ];
    }

    private static function clamp(int $v): int
    {
        return max(0, min(100, $v));
    }

    /**
     * @return array<string, mixed>
     */
    private static function decodeJson(mixed $value): array
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

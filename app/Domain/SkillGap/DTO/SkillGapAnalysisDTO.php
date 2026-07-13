<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\SkillGap\DTO;

final class SkillGapAnalysisDTO
{
    /**
     * @param  array<string, mixed>  $analysis
     */
    public function __construct(
        public readonly int $id,
        public readonly int $resumeId,
        public readonly int $userId,
        public readonly int $jobId,
        public readonly string $jobTitle,
        public readonly int $gapPercentage,
        public readonly int $readinessScore,
        public readonly int $matchSkillsScore,
        public readonly int $matchedCount,
        public readonly int $missingCount,
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
            (int) ($row['resume_id'] ?? 0),
            (int) ($row['user_id'] ?? 0),
            (int) ($row['job_id'] ?? 0),
            (string) ($row['job_title'] ?? ''),
            max(0, min(100, (int) ($row['gap_percentage'] ?? 0))),
            max(0, min(100, (int) ($row['readiness_score'] ?? 0))),
            max(0, min(100, (int) ($row['match_skills_score'] ?? 0))),
            max(0, (int) ($row['matched_count'] ?? 0)),
            max(0, (int) ($row['missing_count'] ?? 0)),
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
            'job_id' => $this->jobId,
            'gap_percentage' => $this->gapPercentage,
            'readiness_score' => $this->readinessScore,
            'match_skills_score' => $this->matchSkillsScore,
            'matched_count' => $this->matchedCount,
            'missing_count' => $this->missingCount,
            'job_title' => $this->jobTitle,
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
            'resume_id' => $this->resumeId,
            'user_id' => $this->userId,
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

<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\CareerCoach\DTO;

/**
 * Active career coaching pack for a resume.
 */
final class CareerCoachSessionDTO
{
    /**
     * @param  array<string, mixed>  $summary
     * @param  list<array<string, mixed>>  $skillGaps
     * @param  list<array<string, mixed>>  $nextRoles
     * @param  list<array<string, mixed>>  $learningRoadmap
     * @param  list<array<string, mixed>>  $certificationRecs
     * @param  list<array<string, mixed>>  $portfolioRecs
     * @param  list<array<string, mixed>>  $jobOpportunities
     * @param  array<string, mixed>  $contextScores
     */
    public function __construct(
        public readonly int $resumeId,
        public readonly int $userId,
        public readonly ?string $targetRole,
        public readonly string $headline,
        public readonly array $summary,
        public readonly array $skillGaps,
        public readonly array $nextRoles,
        public readonly array $learningRoadmap,
        public readonly array $certificationRecs,
        public readonly array $portfolioRecs,
        public readonly array $jobOpportunities,
        public readonly array $contextScores,
        public readonly string $coachVersion,
        public readonly string $calculatedAt,
        public readonly bool $canEdit,
    ) {
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromRow(array $row, bool $canEdit): self
    {
        return new self(
            (int) ($row['resume_id'] ?? 0),
            (int) ($row['user_id'] ?? 0),
            isset($row['target_role']) && $row['target_role'] !== null && $row['target_role'] !== ''
                ? (string) $row['target_role']
                : null,
            (string) ($row['headline'] ?? ''),
            self::decodeMap($row['summary_json'] ?? []),
            self::decodeList($row['skill_gaps_json'] ?? []),
            self::decodeList($row['next_roles_json'] ?? []),
            self::decodeList($row['learning_roadmap_json'] ?? []),
            self::decodeList($row['certification_recs_json'] ?? []),
            self::decodeList($row['portfolio_recs_json'] ?? []),
            self::decodeList($row['job_opportunities_json'] ?? []),
            self::decodeMap($row['context_scores_json'] ?? []),
            (string) ($row['coach_version'] ?? ''),
            (string) ($row['calculated_at'] ?? ''),
            $canEdit,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toPersistPayload(): array
    {
        return [
            'target_role' => $this->targetRole,
            'headline' => $this->headline,
            'summary_json' => $this->summary,
            'skill_gaps_json' => $this->skillGaps,
            'next_roles_json' => $this->nextRoles,
            'learning_roadmap_json' => $this->learningRoadmap,
            'certification_recs_json' => $this->certificationRecs,
            'portfolio_recs_json' => $this->portfolioRecs,
            'job_opportunities_json' => $this->jobOpportunities,
            'context_scores_json' => $this->contextScores,
            'coach_version' => $this->coachVersion,
            'calculated_at' => $this->calculatedAt,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toHistorySnapshot(): array
    {
        return array_merge($this->toPersistPayload(), [
            'resume_id' => $this->resumeId,
            'user_id' => $this->userId,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function decodeMap(mixed $value): array
    {
        $data = self::decodeJson($value);

        return is_array($data) ? $data : [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function decodeList(mixed $value): array
    {
        $data = self::decodeJson($value);
        if (!is_array($data)) {
            return [];
        }
        $out = [];
        foreach ($data as $item) {
            if (is_array($item)) {
                $out[] = $item;
            }
        }

        return $out;
    }

    /**
     * @return array<mixed>
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

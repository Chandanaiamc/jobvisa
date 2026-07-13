<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\JobSearchCopilot\DTO;

final class JobSearchCopilotPlanDTO
{
    /**
     * @param  array<string, mixed>  $plan
     */
    public function __construct(
        public readonly int $id,
        public readonly int $resumeId,
        public readonly int $userId,
        public readonly string $careerGoal,
        public readonly int $copilotScore,
        public readonly int $recommendationCount,
        public readonly array $plan,
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
            (string) ($row['career_goal'] ?? ''),
            max(0, min(100, (int) ($row['copilot_score'] ?? 0))),
            max(0, (int) ($row['recommendation_count'] ?? 0)),
            self::decodeMap($row['plan_json'] ?? []),
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
            'career_goal' => $this->careerGoal,
            'copilot_score' => $this->copilotScore,
            'recommendation_count' => $this->recommendationCount,
            'plan_json' => $this->plan,
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

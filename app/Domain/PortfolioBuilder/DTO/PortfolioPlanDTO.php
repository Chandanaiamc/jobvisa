<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\PortfolioBuilder\DTO;

final class PortfolioPlanDTO
{
    /**
     * @param  array<string, mixed>  $plan
     */
    public function __construct(
        public readonly int $id,
        public readonly int $resumeId,
        public readonly int $userId,
        public readonly ?int $jobId,
        public readonly string $careerGoal,
        public readonly int $strengthScore,
        public readonly int $projectCount,
        public readonly int $recruiterScore,
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
        $jobId = $row['job_id'] ?? null;

        return new self(
            (int) ($row['id'] ?? 0),
            (int) ($row['resume_id'] ?? 0),
            (int) ($row['user_id'] ?? 0),
            $jobId !== null && (int) $jobId > 0 ? (int) $jobId : null,
            (string) ($row['career_goal'] ?? ''),
            max(0, min(100, (int) ($row['strength_score'] ?? 0))),
            max(0, (int) ($row['project_count'] ?? 0)),
            max(0, min(100, (int) ($row['recruiter_score'] ?? 0))),
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
            'job_id' => $this->jobId,
            'career_goal' => $this->careerGoal,
            'strength_score' => $this->strengthScore,
            'project_count' => $this->projectCount,
            'recruiter_score' => $this->recruiterScore,
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

<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\LearningPath\DTO;

final class LearningPathDTO
{
    /**
     * @param  array<string, mixed>  $path
     */
    public function __construct(
        public readonly int $id,
        public readonly int $resumeId,
        public readonly int $userId,
        public readonly ?int $jobId,
        public readonly string $careerGoal,
        public readonly int $timelineWeeks,
        public readonly int $progressPercent,
        public readonly int $milestonesTotal,
        public readonly int $milestonesDone,
        public readonly int $alignmentScore,
        public readonly array $path,
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
            max(0, (int) ($row['timeline_weeks'] ?? 0)),
            max(0, min(100, (int) ($row['progress_percent'] ?? 0))),
            max(0, (int) ($row['milestones_total'] ?? 0)),
            max(0, (int) ($row['milestones_done'] ?? 0)),
            max(0, min(100, (int) ($row['alignment_score'] ?? 0))),
            self::decodeMap($row['path_json'] ?? []),
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
            'timeline_weeks' => $this->timelineWeeks,
            'progress_percent' => $this->progressPercent,
            'milestones_total' => $this->milestonesTotal,
            'milestones_done' => $this->milestonesDone,
            'alignment_score' => $this->alignmentScore,
            'path_json' => $this->path,
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

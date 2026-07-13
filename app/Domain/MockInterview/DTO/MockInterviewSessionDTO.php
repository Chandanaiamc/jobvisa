<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\MockInterview\DTO;

final class MockInterviewSessionDTO
{
    /**
     * @param  array<string, mixed>  $session
     */
    public function __construct(
        public readonly int $id,
        public readonly int $resumeId,
        public readonly int $userId,
        public readonly ?int $jobId,
        public readonly string $jobTitle,
        public readonly string $careerLevel,
        public readonly string $status,
        public readonly int $overallScore,
        public readonly int $communicationScore,
        public readonly int $technicalScore,
        public readonly int $confidenceScore,
        public readonly int $starScore,
        public readonly array $session,
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
            (string) ($row['job_title'] ?? ''),
            (string) ($row['career_level'] ?? ''),
            (string) ($row['status'] ?? 'generated'),
            max(0, min(100, (int) ($row['overall_score'] ?? 0))),
            max(0, min(100, (int) ($row['communication_score'] ?? 0))),
            max(0, min(100, (int) ($row['technical_score'] ?? 0))),
            max(0, min(100, (int) ($row['confidence_score'] ?? 0))),
            max(0, min(100, (int) ($row['star_score'] ?? 0))),
            self::decodeMap($row['session_json'] ?? []),
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
            'job_title' => $this->jobTitle,
            'career_level' => $this->careerLevel,
            'status' => $this->status,
            'overall_score' => $this->overallScore,
            'communication_score' => $this->communicationScore,
            'technical_score' => $this->technicalScore,
            'confidence_score' => $this->confidenceScore,
            'star_score' => $this->starScore,
            'session_json' => $this->session,
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

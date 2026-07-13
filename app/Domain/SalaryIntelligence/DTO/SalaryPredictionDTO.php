<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\SalaryIntelligence\DTO;

final class SalaryPredictionDTO
{
    /**
     * @param  array<string, mixed>  $analysis
     */
    public function __construct(
        public readonly int $id,
        public readonly int $resumeId,
        public readonly int $userId,
        public readonly string $currency,
        public readonly float $predictedSalary,
        public readonly float $minSalary,
        public readonly float $maxSalary,
        public readonly float $marketAverage,
        public readonly float $recommendedTarget,
        public readonly int $confidenceScore,
        public readonly string $careerLevel,
        public readonly string $jobTitle,
        public readonly string $locationLabel,
        public readonly string $industry,
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
            strtoupper(substr((string) ($row['currency'] ?? 'USD'), 0, 3)),
            (float) ($row['predicted_salary'] ?? 0),
            (float) ($row['min_salary'] ?? 0),
            (float) ($row['max_salary'] ?? 0),
            (float) ($row['market_average'] ?? 0),
            (float) ($row['recommended_target'] ?? 0),
            max(0, min(100, (int) ($row['confidence_score'] ?? 0))),
            (string) ($row['career_level'] ?? ''),
            (string) ($row['job_title'] ?? ''),
            (string) ($row['location_label'] ?? ''),
            (string) ($row['industry'] ?? ''),
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
            'currency' => $this->currency,
            'predicted_salary' => $this->predictedSalary,
            'min_salary' => $this->minSalary,
            'max_salary' => $this->maxSalary,
            'market_average' => $this->marketAverage,
            'recommended_target' => $this->recommendedTarget,
            'confidence_score' => $this->confidenceScore,
            'career_level' => $this->careerLevel,
            'job_title' => $this->jobTitle,
            'location_label' => $this->locationLabel,
            'industry' => $this->industry,
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

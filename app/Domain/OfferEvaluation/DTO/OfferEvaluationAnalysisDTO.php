<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\OfferEvaluation\DTO;

final class OfferEvaluationAnalysisDTO
{
    /**
     * @param  array<string, mixed>  $analysis
     */
    public function __construct(
        public readonly int $id,
        public readonly int $resumeId,
        public readonly int $userId,
        public readonly ?int $jobId,
        public readonly string $companyName,
        public readonly string $jobTitle,
        public readonly string $currency,
        public readonly float $baseSalary,
        public readonly int $overallScore,
        public readonly int $compensationScore,
        public readonly int $benefitsScore,
        public readonly int $growthScore,
        public readonly int $lifestyleScore,
        public readonly string $recommendation,
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
        $jobId = $row['job_id'] ?? null;

        return new self(
            (int) ($row['id'] ?? 0),
            (int) ($row['resume_id'] ?? 0),
            (int) ($row['user_id'] ?? 0),
            $jobId !== null && (int) $jobId > 0 ? (int) $jobId : null,
            (string) ($row['company_name'] ?? ''),
            (string) ($row['job_title'] ?? ''),
            (string) ($row['currency'] ?? 'USD'),
            (float) ($row['base_salary'] ?? 0),
            max(0, min(100, (int) ($row['overall_score'] ?? 0))),
            max(0, min(100, (int) ($row['compensation_score'] ?? 0))),
            max(0, min(100, (int) ($row['benefits_score'] ?? 0))),
            max(0, min(100, (int) ($row['growth_score'] ?? 0))),
            max(0, min(100, (int) ($row['lifestyle_score'] ?? 0))),
            (string) ($row['recommendation'] ?? 'negotiate'),
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
            'company_name' => $this->companyName,
            'job_title' => $this->jobTitle,
            'currency' => $this->currency,
            'base_salary' => $this->baseSalary,
            'overall_score' => $this->overallScore,
            'compensation_score' => $this->compensationScore,
            'benefits_score' => $this->benefitsScore,
            'growth_score' => $this->growthScore,
            'lifestyle_score' => $this->lifestyleScore,
            'recommendation' => $this->recommendation,
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

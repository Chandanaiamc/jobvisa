<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Intelligence\DTO;

use JobVisa\App\Domain\Resume\Intelligence\Support\StrengthLevel;

/**
 * Persisted / display snapshot of resume intelligence scores (Sprint 2F.1 + 2F.2).
 */
final class ResumeIntelligenceDTO
{
    /**
     * @param  array<string, array{label: string, weight: int, earned: int, explain?: array<string, mixed>}>  $breakdown
     * @param  list<RecommendationDTO>  $recommendations
     * @param  array<string, mixed>  $analysis  keyword + skill-gap payload
     */
    public function __construct(
        public readonly int $resumeId,
        public readonly int $overallScore,
        public readonly int $atsScore,
        public readonly int $employerReadinessScore,
        public readonly int $keywordMatchScore,
        public readonly string $strengthLevel,
        public readonly array $breakdown,
        public readonly array $recommendations,
        public readonly array $analysis,
        public readonly string $rulesVersion,
        public readonly string $calculatedAt,
        public readonly bool $canEdit,
        public readonly ?string $targetRole = null,
    ) {
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>|null  $breakdown
     * @param  list<array<string, mixed>>|null  $recommendations
     * @param  array<string, mixed>|null  $analysis
     */
    public static function fromRow(
        array $row,
        bool $canEdit,
        ?array $breakdown = null,
        ?array $recommendations = null,
        ?array $analysis = null,
    ): self {
        $breakdown ??= self::decodeJson($row['score_breakdown'] ?? null);
        $recsRaw = $recommendations ?? self::decodeJsonList($row['recommendations'] ?? null);
        $analysis ??= self::decodeJson($row['analysis_json'] ?? null);
        $recs = [];
        foreach ($recsRaw as $item) {
            if (is_array($item)) {
                $recs[] = RecommendationDTO::fromArray($item);
            }
        }

        $overall = max(0, min(100, (int) ($row['overall_score'] ?? 0)));
        $keyword = max(0, min(100, (int) ($row['keyword_match_score'] ?? ($analysis['keyword_matching']['score'] ?? 0))));

        return new self(
            resumeId: (int) ($row['resume_id'] ?? 0),
            overallScore: $overall,
            atsScore: max(0, min(100, (int) ($row['ats_score'] ?? 0))),
            employerReadinessScore: max(0, min(100, (int) ($row['employer_readiness_score'] ?? 0))),
            keywordMatchScore: $keyword,
            strengthLevel: (string) ($row['strength_level'] ?? StrengthLevel::fromScore($overall)),
            breakdown: is_array($breakdown) ? $breakdown : [],
            recommendations: $recs,
            analysis: is_array($analysis) ? $analysis : [],
            rulesVersion: (string) ($row['rules_version'] ?? ''),
            calculatedAt: (string) ($row['calculated_at'] ?? ''),
            canEdit: $canEdit,
            targetRole: isset($row['target_role']) ? (string) $row['target_role'] : ($analysis['target_role'] ?? null),
        );
    }

    public function strengthLabel(): string
    {
        return StrengthLevel::label($this->strengthLevel);
    }

    /**
     * @return array<string, mixed>
     */
    public function keywordAnalysis(): array
    {
        $block = $this->analysis['keyword_matching'] ?? [];

        return is_array($block) ? $block : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function skillGapAnalysis(): array
    {
        $block = $this->analysis['skill_gaps'] ?? [];

        return is_array($block) ? $block : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'resume_id' => $this->resumeId,
            'overall_score' => $this->overallScore,
            'ats_score' => $this->atsScore,
            'employer_readiness_score' => $this->employerReadinessScore,
            'keyword_match_score' => $this->keywordMatchScore,
            'strength_level' => $this->strengthLevel,
            'strength_label' => $this->strengthLabel(),
            'score_breakdown' => $this->breakdown,
            'recommendations' => array_map(static fn (RecommendationDTO $r): array => $r->toArray(), $this->recommendations),
            'analysis' => $this->analysis,
            'target_role' => $this->targetRole,
            'rules_version' => $this->rulesVersion,
            'calculated_at' => $this->calculatedAt,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return [
            'overall_score' => $this->overallScore,
            'ats_score' => $this->atsScore,
            'employer_readiness_score' => $this->employerReadinessScore,
            'keyword_match_score' => $this->keywordMatchScore,
            'strength_level' => $this->strengthLevel,
            'strength_label' => $this->strengthLabel(),
            'rules_version' => $this->rulesVersion,
            'calculated_at' => $this->calculatedAt,
        ];
    }

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

    /**
     * @return list<mixed>
     */
    private static function decodeJsonList(mixed $value): array
    {
        $decoded = self::decodeJson($value);

        return array_values($decoded);
    }
}

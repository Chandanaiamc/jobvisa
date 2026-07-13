<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Intelligence\Services;

use JobVisa\App\Domain\Resume\Intelligence\DTO\RecommendationDTO;

/**
 * Deduplicate and prioritize recommendations by severity then improvement.
 */
final class ResumeIntelligenceRecommendationService
{
    private const SEVERITY_ORDER = [
        'high' => 0,
        'medium' => 1,
        'low' => 2,
        'info' => 3,
    ];

    /**
     * @param  list<RecommendationDTO>  $recommendations
     * @return list<RecommendationDTO>
     */
    public function prioritize(array $recommendations, int $limit = 12): array
    {
        $unique = [];
        foreach ($recommendations as $rec) {
            if (!$rec instanceof RecommendationDTO || $rec->code === '') {
                continue;
            }
            if (!isset($unique[$rec->code])
                || $rec->estimatedImprovement > $unique[$rec->code]->estimatedImprovement) {
                $unique[$rec->code] = $rec;
            }
        }

        $list = array_values($unique);
        usort($list, static function (RecommendationDTO $a, RecommendationDTO $b): int {
            $sa = self::SEVERITY_ORDER[$a->severity] ?? 9;
            $sb = self::SEVERITY_ORDER[$b->severity] ?? 9;
            if ($sa !== $sb) {
                return $sa <=> $sb;
            }

            return $b->estimatedImprovement <=> $a->estimatedImprovement;
        });

        return array_slice($list, 0, max(1, $limit));
    }
}

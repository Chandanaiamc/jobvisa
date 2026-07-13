<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Intelligence\DTO;

/**
 * Result of a single scoring rule (0–weight points + recommendations).
 */
final class RuleResult
{
    /**
     * @param  list<RecommendationDTO>  $recommendations
     * @param  array<string, mixed>  $explain
     */
    public function __construct(
        public readonly int $earned,
        public readonly int $weight,
        public readonly array $recommendations = [],
        public readonly array $explain = [],
    ) {
    }

    public function ratio(): float
    {
        if ($this->weight < 1) {
            return 0.0;
        }

        return max(0.0, min(1.0, $this->earned / $this->weight));
    }
}

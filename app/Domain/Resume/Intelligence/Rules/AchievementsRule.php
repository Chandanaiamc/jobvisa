<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Intelligence\Rules;

use JobVisa\App\Domain\Resume\Intelligence\DTO\ResumeIntelligenceContext;
use JobVisa\App\Domain\Resume\Intelligence\DTO\RuleResult;

final class AchievementsRule extends AbstractIntelligenceRule
{
    public function code(): string { return 'achievements'; }
    public function category(): string { return 'achievements'; }
    public function label(): string { return 'Achievements'; }
    public function weight(): int { return 5; }

    public function evaluate(ResumeIntelligenceContext $context): RuleResult
    {
        $count = count($context->achievements);
        $recs = [];
        $earned = match (true) {
            $count >= 2 => 5,
            $count === 1 => 3,
            default => 0,
        };
        if ($count === 0) {
            $recs[] = $this->rec($context, 'ACH_MISSING', 'Add achievements', 'Add awards or achievements that demonstrate impact.', 'low', 'achievements', 3);
        }

        return $this->result($earned, $this->weight(), $recs, ['count' => $count]);
    }
}

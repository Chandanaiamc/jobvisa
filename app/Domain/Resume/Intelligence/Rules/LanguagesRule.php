<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Intelligence\Rules;

use JobVisa\App\Domain\Resume\Intelligence\DTO\ResumeIntelligenceContext;
use JobVisa\App\Domain\Resume\Intelligence\DTO\RuleResult;

final class LanguagesRule extends AbstractIntelligenceRule
{
    public function code(): string { return 'languages'; }
    public function category(): string { return 'languages'; }
    public function label(): string { return 'Languages'; }
    public function weight(): int { return 5; }

    public function evaluate(ResumeIntelligenceContext $context): RuleResult
    {
        $count = count($context->languages);
        $recs = [];
        $earned = match (true) {
            $count >= 2 => 5,
            $count === 1 => 3,
            default => 0,
        };
        if ($count === 0) {
            $recs[] = $this->rec($context, 'LANG_MISSING', 'Add languages', 'Add at least one language with proficiency level.', 'medium', 'languages', 5);
        }

        return $this->result($earned, $this->weight(), $recs, ['count' => $count]);
    }
}

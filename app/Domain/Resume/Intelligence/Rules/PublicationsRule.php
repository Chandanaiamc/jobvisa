<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Intelligence\Rules;

use JobVisa\App\Domain\Resume\Intelligence\DTO\ResumeIntelligenceContext;
use JobVisa\App\Domain\Resume\Intelligence\DTO\RuleResult;

final class PublicationsRule extends AbstractIntelligenceRule
{
    public function code(): string { return 'publications'; }
    public function category(): string { return 'publications'; }
    public function label(): string { return 'Publications'; }
    public function weight(): int { return 5; }

    public function evaluate(ResumeIntelligenceContext $context): RuleResult
    {
        $count = count($context->publications);
        $recs = [];
        $earned = match (true) {
            $count >= 1 => 5,
            default => 0,
        };
        if ($count === 0) {
            $recs[] = $this->rec($context, 'PUB_OPTIONAL', 'Consider adding publications', 'Optional: add research or media publications if relevant to your field.', 'info', 'publications', 3);
        }

        return $this->result($earned, $this->weight(), $recs, ['count' => $count]);
    }
}

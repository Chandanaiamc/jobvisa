<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Intelligence\Rules;

use JobVisa\App\Domain\Resume\Intelligence\DTO\ResumeIntelligenceContext;
use JobVisa\App\Domain\Resume\Intelligence\DTO\RuleResult;

/** References presence — never scores contact details content. Weight 5. */
final class ReferencesRule extends AbstractIntelligenceRule
{
    public function code(): string { return 'references'; }
    public function category(): string { return 'references'; }
    public function label(): string { return 'References'; }
    public function weight(): int { return 5; }

    public function evaluate(ResumeIntelligenceContext $context): RuleResult
    {
        $count = count($context->references);
        $recs = [];
        $earned = match (true) {
            $count >= 2 => 5,
            $count === 1 => 3,
            default => 0,
        };
        if ($count === 0) {
            $recs[] = $this->rec($context, 'REF_MISSING', 'Add professional references', 'Add references you are permitted to share. Contact details stay private unless you grant permission.', 'low', 'references', 3);
        }

        return $this->result($earned, $this->weight(), $recs, ['count' => $count]);
    }
}

<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Intelligence\Rules;

use JobVisa\App\Domain\Resume\Intelligence\DTO\ResumeIntelligenceContext;
use JobVisa\App\Domain\Resume\Intelligence\DTO\RuleResult;

final class PortfolioRule extends AbstractIntelligenceRule
{
    public function code(): string { return 'portfolio'; }
    public function category(): string { return 'portfolio'; }
    public function label(): string { return 'Portfolio'; }
    public function weight(): int { return 5; }

    public function evaluate(ResumeIntelligenceContext $context): RuleResult
    {
        $count = count($context->portfolio);
        $recs = [];
        $earned = match (true) {
            $count >= 1 => 5,
            default => 0,
        };
        if ($count === 0) {
            $recs[] = $this->rec($context, 'PORT_OPTIONAL', 'Add portfolio items', 'Optional: link portfolio work (GitHub, Behance, case studies) when relevant.', 'info', 'portfolio', 3);
        }

        return $this->result($earned, $this->weight(), $recs, ['count' => $count]);
    }
}

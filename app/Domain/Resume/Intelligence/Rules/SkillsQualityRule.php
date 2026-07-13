<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Intelligence\Rules;

use JobVisa\App\Domain\Resume\Intelligence\DTO\ResumeIntelligenceContext;
use JobVisa\App\Domain\Resume\Intelligence\DTO\RuleResult;

/** Skills quality. Weight 10. */
final class SkillsQualityRule extends AbstractIntelligenceRule
{
    public function code(): string
    {
        return 'skills';
    }

    public function category(): string
    {
        return 'skills';
    }

    public function label(): string
    {
        return 'Skills quality';
    }

    public function weight(): int
    {
        return 10;
    }

    public function evaluate(ResumeIntelligenceContext $context): RuleResult
    {
        $count = count($context->skills);
        $recs = [];
        if ($count === 0) {
            $recs[] = $this->rec($context, 'SKILLS_MISSING', 'Add at least five relevant skills', 'Add skills that match your target roles (aim for 5+).', 'high', 'skills', 10);

            return $this->result(0, $this->weight(), $recs, ['count' => 0]);
        }

        $earned = match (true) {
            $count >= 8 => 10,
            $count >= 5 => 8,
            $count >= 3 => 5,
            default => 2,
        };

        if ($count < 5) {
            $recs[] = $this->rec($context, 'SKILLS_MORE', 'Add at least five relevant skills', 'You have ' . $count . ' skill(s). Add more relevant skills to strengthen matching.', 'medium', 'skills', max(1, 8 - $earned));
        }

        return $this->result($earned, $this->weight(), $recs, ['count' => $count]);
    }
}

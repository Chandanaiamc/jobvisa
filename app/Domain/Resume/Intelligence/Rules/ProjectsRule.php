<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Intelligence\Rules;

use JobVisa\App\Domain\Resume\Intelligence\DTO\ResumeIntelligenceContext;
use JobVisa\App\Domain\Resume\Intelligence\DTO\RuleResult;

final class ProjectsRule extends AbstractIntelligenceRule
{
    public function code(): string { return 'projects'; }
    public function category(): string { return 'projects'; }
    public function label(): string { return 'Projects'; }
    public function weight(): int { return 8; }

    public function evaluate(ResumeIntelligenceContext $context): RuleResult
    {
        $count = count($context->projects);
        $recs = [];
        $earned = match (true) {
            $count >= 2 => 8,
            $count === 1 => 5,
            default => 0,
        };
        if ($count === 0) {
            $recs[] = $this->rec($context, 'PROJ_MISSING', 'Add projects', 'Showcase at least one project with a clear description.', 'medium', 'projects', 5);
        }

        return $this->result($earned, $this->weight(), $recs, ['count' => $count]);
    }
}

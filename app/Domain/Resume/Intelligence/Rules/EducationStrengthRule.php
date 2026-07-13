<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Intelligence\Rules;

use JobVisa\App\Domain\Resume\Intelligence\DTO\ResumeIntelligenceContext;
use JobVisa\App\Domain\Resume\Intelligence\DTO\RuleResult;

/** Education strength. Weight 10. */
final class EducationStrengthRule extends AbstractIntelligenceRule
{
    public function code(): string
    {
        return 'education';
    }

    public function category(): string
    {
        return 'education';
    }

    public function label(): string
    {
        return 'Education strength';
    }

    public function weight(): int
    {
        return 10;
    }

    public function evaluate(ResumeIntelligenceContext $context): RuleResult
    {
        $count = count($context->education);
        $recs = [];
        if ($count === 0) {
            $recs[] = $this->rec($context, 'EDU_MISSING', 'Add education history', 'Add at least one education entry with institution and qualification.', 'high', 'education', 10);

            return $this->result(0, $this->weight(), $recs, ['count' => 0]);
        }

        $complete = 0;
        foreach ($context->education as $row) {
            $school = trim((string) ($row['institution'] ?? $row['school'] ?? ''));
            $degree = trim((string) ($row['degree'] ?? $row['qualification_type'] ?? ''));
            if ($school !== '' && $degree !== '') {
                $complete++;
            }
        }

        $earned = min(6, $count * 3);
        if ($complete > 0) {
            $earned += min(4, $complete * 2);
        } else {
            $recs[] = $this->rec($context, 'EDU_INCOMPLETE', 'Complete education details', 'Include institution and qualification/degree on each education entry.', 'medium', 'education', 4);
        }

        return $this->result($earned, $this->weight(), $recs, ['count' => $count, 'complete' => $complete]);
    }
}

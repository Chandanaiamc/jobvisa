<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Intelligence\Rules;

use JobVisa\App\Domain\Resume\Intelligence\DTO\ResumeIntelligenceContext;
use JobVisa\App\Domain\Resume\Intelligence\DTO\RuleResult;

/** Experience strength. Weight 14. */
final class ExperienceStrengthRule extends AbstractIntelligenceRule
{
    public function code(): string
    {
        return 'experience';
    }

    public function category(): string
    {
        return 'experience';
    }

    public function label(): string
    {
        return 'Experience strength';
    }

    public function weight(): int
    {
        return 14;
    }

    public function evaluate(ResumeIntelligenceContext $context): RuleResult
    {
        $count = count($context->experience);
        $recs = [];
        if ($count === 0) {
            $recs[] = $this->rec($context, 'EXP_MISSING', 'Add work experience', 'Add work experience with company, title, dates, and responsibilities.', 'high', 'experience', 14);

            return $this->result(0, $this->weight(), $recs, ['count' => 0]);
        }

        $complete = $context->completeExperienceCount();
        $measurable = $context->experienceWithMeasurableAchievements();

        $earned = min(6, $count * 2);
        $earned += min(5, $complete * 3);
        if ($measurable > 0) {
            $earned += min(3, $measurable * 2);
        } else {
            $recs[] = $this->rec($context, 'EXP_MEASURABLE', 'Add measurable achievements to work experience', 'Include quantified outcomes (numbers, %, impact) in experience achievements.', 'high', 'experience', 3);
        }

        if ($complete < 1) {
            $recs[] = $this->rec($context, 'EXP_INCOMPLETE', 'Complete work experience details', 'Each role should include job title, company, start date, and a clear description.', 'high', 'experience', 5);
        }

        return $this->result($earned, $this->weight(), $recs, [
            'count' => $count,
            'complete' => $complete,
            'measurable' => $measurable,
        ]);
    }
}

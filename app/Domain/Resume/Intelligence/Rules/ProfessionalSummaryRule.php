<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Intelligence\Rules;

use JobVisa\App\Domain\Resume\Intelligence\DTO\ResumeIntelligenceContext;
use JobVisa\App\Domain\Resume\Intelligence\DTO\RuleResult;

/** Professional summary quality. Weight 12. */
final class ProfessionalSummaryRule extends AbstractIntelligenceRule
{
    public function code(): string
    {
        return 'professional_summary';
    }

    public function category(): string
    {
        return 'professional_summary';
    }

    public function label(): string
    {
        return 'Professional summary quality';
    }

    public function weight(): int
    {
        return 12;
    }

    public function evaluate(ResumeIntelligenceContext $context): RuleResult
    {
        $earned = 0;
        $recs = [];
        $headlineLen = $context->headlineLength();
        $summaryLen = $context->summaryLength();

        if ($context->hasHeadline && $headlineLen >= 12) {
            $earned += 4;
        } else {
            $recs[] = $this->rec($context, 'SUMMARY_HEADLINE', 'Add a professional headline', 'Write a clear headline (at least ~12 characters) describing your role.', 'high', 'professional', 4);
        }

        if ($summaryLen >= 200) {
            $earned += 6;
        } elseif ($summaryLen >= 80) {
            $earned += 3;
            $recs[] = $this->rec($context, 'SUMMARY_EXPAND', 'Strengthen your summary', 'Expand your professional summary to at least ~200 characters with role focus and strengths.', 'medium', 'professional', 3);
        } else {
            $recs[] = $this->rec($context, 'SUMMARY_MISSING', 'Complete your professional summary', 'Add a professional summary so employers understand your background quickly.', 'high', 'professional', 6);
        }

        if ($context->hasCurrentRole || $context->hasCareerObjective) {
            $earned += 2;
        } else {
            $recs[] = $this->rec($context, 'SUMMARY_ROLE', 'Add current role or career objective', 'Include your current job title/company or a short career objective.', 'low', 'professional', 2);
        }

        return $this->result($earned, $this->weight(), $recs, [
            'headline_length' => $headlineLen,
            'summary_length' => $summaryLen,
        ]);
    }
}

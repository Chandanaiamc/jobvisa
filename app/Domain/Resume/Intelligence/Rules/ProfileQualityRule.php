<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Intelligence\Rules;

use JobVisa\App\Domain\Resume\Intelligence\DTO\ResumeIntelligenceContext;
use JobVisa\App\Domain\Resume\Intelligence\DTO\RuleResult;

/** Profile quality — contact identity only (no protected characteristics). Weight 10. */
final class ProfileQualityRule extends AbstractIntelligenceRule
{
    public function code(): string
    {
        return 'profile_quality';
    }

    public function category(): string
    {
        return 'profile_quality';
    }

    public function label(): string
    {
        return 'Profile quality';
    }

    public function weight(): int
    {
        return 10;
    }

    public function evaluate(ResumeIntelligenceContext $context): RuleResult
    {
        $earned = 0;
        $recs = [];
        if ($context->hasDisplayName) {
            $earned += 4;
        } else {
            $recs[] = $this->rec($context, 'PROFILE_NAME', 'Add your display name', 'Add your first and last name so employers can identify you.', 'high', 'personal', 4);
        }
        if ($context->hasPhone) {
            $earned += 3;
        } else {
            $recs[] = $this->rec($context, 'PROFILE_PHONE', 'Add a phone number', 'Add a reachable phone number to improve contact readiness.', 'high', 'personal', 3);
        }
        if ($context->hasLocation) {
            $earned += 3;
        } else {
            $recs[] = $this->rec($context, 'PROFILE_LOCATION', 'Add your location', 'Add current country and city (optional privacy — scoring does not require other personal details).', 'medium', 'personal', 3);
        }

        return $this->result($earned, $this->weight(), $recs, [
            'has_display_name' => $context->hasDisplayName,
            'has_phone' => $context->hasPhone,
            'has_location' => $context->hasLocation,
        ]);
    }
}

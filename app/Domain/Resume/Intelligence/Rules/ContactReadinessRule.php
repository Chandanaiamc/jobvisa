<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Intelligence\Rules;

use JobVisa\App\Domain\Resume\Intelligence\DTO\ResumeIntelligenceContext;
use JobVisa\App\Domain\Resume\Intelligence\DTO\RuleResult;

/** Contact readiness — email/phone/location/CV. Does not penalize private optional data. Weight 5. */
final class ContactReadinessRule extends AbstractIntelligenceRule
{
    public function code(): string { return 'contact_readiness'; }
    public function category(): string { return 'contact_readiness'; }
    public function label(): string { return 'Contact readiness'; }
    public function weight(): int { return 5; }

    public function evaluate(ResumeIntelligenceContext $context): RuleResult
    {
        $earned = 0;
        $recs = [];
        if ($context->hasEmail) {
            $earned += 2;
        }
        if ($context->hasPhone) {
            $earned += 2;
        } else {
            $recs[] = $this->rec($context, 'CONTACT_PHONE', 'Add contact phone', 'Provide a phone number employers can use after you apply.', 'medium', 'personal', 2);
        }
        if ($context->hasCvFile) {
            $earned += 1;
        } else {
            $recs[] = $this->rec($context, 'CONTACT_CV', 'Attach a CV file', 'Upload a CV file on the resume settings/overview for download readiness.', 'low', 'overview', 1);
        }

        return $this->result($earned, $this->weight(), $recs, [
            'has_email' => $context->hasEmail,
            'has_phone' => $context->hasPhone,
            'has_cv' => $context->hasCvFile,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Intelligence\Rules;

use JobVisa\App\Domain\Resume\Intelligence\DTO\ResumeIntelligenceContext;
use JobVisa\App\Domain\Resume\Intelligence\DTO\RuleResult;

final class CertificationsRule extends AbstractIntelligenceRule
{
    public function code(): string { return 'certifications'; }
    public function category(): string { return 'certifications'; }
    public function label(): string { return 'Certifications'; }
    public function weight(): int { return 6; }

    public function evaluate(ResumeIntelligenceContext $context): RuleResult
    {
        $count = count($context->certifications);
        $recs = [];
        $earned = match (true) {
            $count >= 2 => 6,
            $count === 1 => 4,
            default => 0,
        };
        if ($count === 0) {
            $recs[] = $this->rec($context, 'CERT_MISSING', 'Add certifications', 'Add relevant certifications to strengthen credibility.', 'low', 'certifications', 4);
        }

        return $this->result($earned, $this->weight(), $recs, ['count' => $count]);
    }
}

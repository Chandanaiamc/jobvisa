<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Intelligence\Contracts;

use JobVisa\App\Domain\Resume\Intelligence\DTO\ResumeIntelligenceContext;
use JobVisa\App\Domain\Resume\Intelligence\DTO\RuleResult;

/**
 * Transparent, deterministic scoring rule for resume intelligence.
 */
interface IntelligenceRuleInterface
{
    public function code(): string;

    public function category(): string;

    public function label(): string;

    /** Maximum points this rule may contribute to the overall score. */
    public function weight(): int;

    public function evaluate(ResumeIntelligenceContext $context): RuleResult;
}

<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Intelligence\Rules;

use JobVisa\App\Domain\Resume\Intelligence\Contracts\IntelligenceRuleInterface;
use JobVisa\App\Domain\Resume\Intelligence\DTO\RecommendationDTO;
use JobVisa\App\Domain\Resume\Intelligence\DTO\ResumeIntelligenceContext;
use JobVisa\App\Domain\Resume\Intelligence\DTO\RuleResult;

abstract class AbstractIntelligenceRule implements IntelligenceRuleInterface
{
    protected function rec(
        ResumeIntelligenceContext $ctx,
        string $code,
        string $title,
        string $message,
        string $severity,
        string $section,
        int $improvement
    ): RecommendationDTO {
        return new RecommendationDTO(
            code: $code,
            title: $title,
            message: $message,
            severity: $severity,
            section: $section,
            estimatedImprovement: $improvement,
            actionUrl: $ctx->sectionUrl($section),
        );
    }

    protected function clamp(int $earned, int $weight): int
    {
        return max(0, min($weight, $earned));
    }

    protected function result(int $earned, int $weight, array $recs = [], array $explain = []): RuleResult
    {
        return new RuleResult($this->clamp($earned, $weight), $weight, $recs, $explain);
    }
}

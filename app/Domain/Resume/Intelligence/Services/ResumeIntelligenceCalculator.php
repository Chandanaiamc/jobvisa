<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Intelligence\Services;

use JobVisa\App\Domain\Resume\Intelligence\Contracts\IntelligenceRuleInterface;
use JobVisa\App\Domain\Resume\Intelligence\DTO\ResumeIntelligenceContext;
use JobVisa\App\Domain\Resume\Intelligence\DTO\ResumeIntelligenceDTO;
use JobVisa\App\Domain\Resume\Intelligence\Support\RulesVersion;
use JobVisa\App\Domain\Resume\Intelligence\Support\StrengthLevel;

/**
 * Deterministic overall + ATS + employer + keyword/skill-gap calculator.
 * Does not touch ResumeCompletionCalculator.
 */
final class ResumeIntelligenceCalculator
{
    /**
     * @param  list<IntelligenceRuleInterface>  $rules
     */
    public function __construct(
        private readonly array $rules,
        private readonly AtsReadinessCalculator $ats,
        private readonly EmployerReadinessCalculator $employer,
        private readonly KeywordMatchingService $keywords,
        private readonly SkillGapAnalysisService $skillGaps,
        private readonly ResumeIntelligenceRecommendationService $recommendations,
    ) {
    }

    public function calculate(
        ResumeIntelligenceContext $context,
        bool $canEdit = true,
        ?string $targetRole = null,
    ): ResumeIntelligenceDTO {
        $breakdown = [];
        $overall = 0;
        $ruleRecs = [];

        foreach ($this->rules as $rule) {
            $result = $rule->evaluate($context);
            $earned = max(0, min($rule->weight(), $result->earned));
            $overall += $earned;
            $breakdown[$rule->category()] = [
                'label' => $rule->label(),
                'weight' => $rule->weight(),
                'earned' => $earned,
                'explain' => $result->explain,
            ];
            foreach ($result->recommendations as $rec) {
                $ruleRecs[] = $rec;
            }
        }

        $overall = max(0, min(100, $overall));
        $ats = $this->ats->score($context);
        $employer = $this->employer->score($context);

        foreach ($this->ats->recommendations($context) as $rec) {
            $ruleRecs[] = $rec;
        }
        foreach ($this->employer->recommendations($context) as $rec) {
            $ruleRecs[] = $rec;
        }

        $keywordAnalysis = $this->keywords->analyze($context, $targetRole);
        $gapAnalysis = $this->skillGaps->analyze($context, $targetRole);

        foreach ($keywordAnalysis['recommendations'] as $rec) {
            $ruleRecs[] = $rec;
        }
        foreach ($gapAnalysis['recommendations'] as $rec) {
            $ruleRecs[] = $rec;
        }

        unset($keywordAnalysis['recommendations'], $gapAnalysis['recommendations']);

        $resolvedRole = (string) ($keywordAnalysis['role'] ?? $gapAnalysis['role'] ?? 'general');
        $analysis = [
            'target_role' => $targetRole !== null && trim($targetRole) !== '' ? trim($targetRole) : $resolvedRole,
            'resolved_role' => $resolvedRole,
            'keyword_matching' => $keywordAnalysis,
            'skill_gaps' => $gapAnalysis,
        ];

        $recs = $this->recommendations->prioritize($ruleRecs);
        $now = (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s.v');

        return new ResumeIntelligenceDTO(
            resumeId: $context->resumeId,
            overallScore: $overall,
            atsScore: $ats,
            employerReadinessScore: $employer,
            keywordMatchScore: (int) ($keywordAnalysis['score'] ?? 0),
            strengthLevel: StrengthLevel::fromScore($overall),
            breakdown: $breakdown,
            recommendations: $recs,
            analysis: $analysis,
            rulesVersion: RulesVersion::CURRENT,
            calculatedAt: $now,
            canEdit: $canEdit,
            targetRole: $analysis['target_role'],
        );
    }

    /**
     * @return list<IntelligenceRuleInterface>
     */
    public function rules(): array
    {
        return $this->rules;
    }
}

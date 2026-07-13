<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Intelligence\Services;

use JobVisa\App\Domain\Resume\Intelligence\DTO\RecommendationDTO;
use JobVisa\App\Domain\Resume\Intelligence\DTO\ResumeIntelligenceContext;

/**
 * Employer review readiness (0–100). Respects visibility; never requires private contact leakage.
 */
final class EmployerReadinessCalculator
{
    public function score(ResumeIntelligenceContext $context): int
    {
        $score = 0;

        if ($context->hasHeadline && $context->summaryLength() >= 80) {
            $score += 20;
        } elseif ($context->hasHeadline || $context->summaryLength() >= 40) {
            $score += 10;
        }

        if (count($context->education) > 0) {
            $score += 15;
        }

        $completeExp = $context->completeExperienceCount();
        if ($completeExp >= 2) {
            $score += 20;
        } elseif ($completeExp === 1) {
            $score += 12;
        } elseif (count($context->experience) > 0) {
            $score += 6;
        }

        $skills = count($context->skills);
        if ($skills >= 5) {
            $score += 15;
        } elseif ($skills >= 3) {
            $score += 10;
        } elseif ($skills > 0) {
            $score += 5;
        }

        if ($context->hasEmail && $context->hasPhone) {
            $score += 10;
        } elseif ($context->hasEmail || $context->hasPhone) {
            $score += 5;
        }

        $publicModules = $context->publicFacingModuleCount();
        if ($publicModules >= 2) {
            $score += 10;
        } elseif ($publicModules === 1) {
            $score += 6;
        }

        if ($context->hasCvFile) {
            $score += 5;
        }

        // Optional bonus — does not penalize private-only references.
        if ($context->referenceCountWithContactPermission() > 0) {
            $score += 5;
        }

        return max(0, min(100, $score));
    }

    /**
     * @return list<RecommendationDTO>
     */
    public function recommendations(ResumeIntelligenceContext $context): array
    {
        $recs = [];
        if (!($context->hasHeadline && $context->summaryLength() >= 80)) {
            $recs[] = new RecommendationDTO('EMP_SUMMARY', 'Complete your professional summary', 'Employers need a headline and readable summary before reviewing details.', 'high', 'professional', 10, $context->sectionUrl('professional'));
        }
        if ($context->publicFacingModuleCount() < 1) {
            $recs[] = new RecommendationDTO('EMP_PUBLIC', 'Publish at least one showcase section', 'Set a project, achievement, publication, or portfolio item to Public or Employers Only.', 'medium', 'projects', 6, $context->sectionUrl('projects'));
        }
        if (!$context->hasCvFile) {
            $recs[] = new RecommendationDTO('EMP_CV', 'Attach a CV file', 'Make a CV file available for employer download from resume settings.', 'low', 'overview', 3, $context->sectionUrl('overview'));
        }

        return $recs;
    }
}

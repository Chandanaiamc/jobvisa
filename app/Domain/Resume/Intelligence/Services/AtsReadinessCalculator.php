<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Intelligence\Services;

use JobVisa\App\Domain\Resume\Intelligence\DTO\RecommendationDTO;
use JobVisa\App\Domain\Resume\Intelligence\DTO\ResumeIntelligenceContext;

/**
 * ATS readiness heuristics (0–100). Not a guarantee of ATS approval.
 */
final class AtsReadinessCalculator
{
    public function score(ResumeIntelligenceContext $context): int
    {
        $score = 0;

        if ($context->hasHeadline && $context->headlineLength() >= 12) {
            $score += 15;
        }

        $summaryLen = $context->summaryLength();
        if ($summaryLen >= 120) {
            $score += 20;
        } elseif ($summaryLen >= 40) {
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
            $score += 5;
        }

        if ($context->experienceWithMeasurableAchievements() > 0) {
            $score += 10;
        }

        $skills = count($context->skills);
        if ($skills >= 5) {
            $score += 15;
        } elseif ($skills >= 3) {
            $score += 8;
        } elseif ($skills > 0) {
            $score += 3;
        }

        if ($context->hasPhone && $context->hasLocation) {
            $score += 5;
        } elseif ($context->hasPhone || $context->hasLocation) {
            $score += 2;
        }

        return max(0, min(100, $score));
    }

    /**
     * @return list<RecommendationDTO>
     */
    public function recommendations(ResumeIntelligenceContext $context): array
    {
        $recs = [];
        if (!$context->hasHeadline || $context->headlineLength() < 12) {
            $recs[] = new RecommendationDTO('ATS_HEADLINE', 'Add a clear headline', 'ATS parsers look for a concise professional headline.', 'high', 'professional', 8, $context->sectionUrl('professional'));
        }
        if ($context->summaryLength() < 120) {
            $recs[] = new RecommendationDTO('ATS_SUMMARY', 'Expand your professional summary', 'A short or missing summary weakens ATS keyword coverage. Aim for ~120+ characters.', 'high', 'professional', 10, $context->sectionUrl('professional'));
        }
        if (count($context->education) < 1) {
            $recs[] = new RecommendationDTO('ATS_EDUCATION', 'Add education', 'Include education history for common ATS screening filters.', 'high', 'education', 10, $context->sectionUrl('education'));
        }
        if ($context->completeExperienceCount() < 1) {
            $recs[] = new RecommendationDTO('ATS_EXPERIENCE', 'Complete work experience', 'Add roles with title, company, dates, and description for ATS parsing.', 'high', 'experience', 12, $context->sectionUrl('experience'));
        }
        if ($context->experienceWithMeasurableAchievements() < 1 && count($context->experience) > 0) {
            $recs[] = new RecommendationDTO('ATS_METRICS', 'Add measurable achievements to work experience', 'Include numbers or outcomes ATS and recruiters can scan quickly.', 'medium', 'experience', 6, $context->sectionUrl('experience'));
        }
        if (count($context->skills) < 5) {
            $recs[] = new RecommendationDTO('ATS_SKILLS', 'Add at least five relevant skills', 'Insufficient skills reduce keyword match potential. This does not guarantee ATS approval.', 'high', 'skills', 8, $context->sectionUrl('skills'));
        }
        if (!$context->hasPhone || !$context->hasLocation) {
            $recs[] = new RecommendationDTO('ATS_CONTACT', 'Complete contact and location details', 'Add phone and location so applications parse cleanly.', 'medium', 'personal', 4, $context->sectionUrl('personal'));
        }

        return $recs;
    }
}

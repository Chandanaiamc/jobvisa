<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Intelligence\Services;

use JobVisa\App\Domain\Resume\Intelligence\DTO\RecommendationDTO;
use JobVisa\App\Domain\Resume\Intelligence\DTO\ResumeIntelligenceContext;
use JobVisa\App\Domain\Resume\Intelligence\Support\RoleSkillTaxonomy;

/**
 * Skill gap analysis vs role taxonomy (explainable).
 */
final class SkillGapAnalysisService
{
    /**
     * @return array{
     *   role: string,
     *   expected: list<string>,
     *   present: list<string>,
     *   gaps: list<string>,
     *   coverage_percent: int,
     *   recommendations: list<RecommendationDTO>
     * }
     */
    public function analyze(ResumeIntelligenceContext $context, ?string $targetRole = null): array
    {
        $role = RoleSkillTaxonomy::resolveRole($targetRole, $context->headline, $this->titleHint($context));
        $expected = RoleSkillTaxonomy::expectedSkills($role);

        $have = [];
        foreach ($context->skills as $row) {
            $name = mb_strtolower(trim((string) ($row['skill_name'] ?? $row['name'] ?? '')));
            if ($name !== '') {
                $have[$name] = $name;
            }
        }

        $present = [];
        $gaps = [];
        foreach ($expected as $skill) {
            $found = false;
            foreach ($have as $owned) {
                if ($owned === $skill || str_contains($owned, $skill) || str_contains($skill, $owned)) {
                    $found = true;
                    break;
                }
            }
            if ($found) {
                $present[] = $skill;
            } else {
                $gaps[] = $skill;
            }
        }

        $coverage = count($expected) > 0
            ? (int) round((count($present) / count($expected)) * 100)
            : 0;

        $recs = [];
        if ($gaps !== []) {
            $top = array_slice($gaps, 0, 5);
            $recs[] = new RecommendationDTO(
                'SKILL_GAP',
                'Close skill gaps for your target role',
                'Consider adding these role-relevant skills if you have real experience: ' . implode(', ', $top) . '.',
                count($gaps) >= 4 ? 'high' : 'medium',
                'skills',
                min(12, count($gaps) * 2),
                $context->sectionUrl('skills'),
            );
        }

        return [
            'role' => $role,
            'expected' => $expected,
            'present' => $present,
            'gaps' => $gaps,
            'coverage_percent' => max(0, min(100, $coverage)),
            'recommendations' => $recs,
        ];
    }

    private function titleHint(ResumeIntelligenceContext $context): string
    {
        foreach ($context->experience as $row) {
            $title = trim((string) ($row['job_title'] ?? ''));
            if ($title !== '') {
                return $title;
            }
        }

        return '';
    }
}

<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Intelligence\Services;

use JobVisa\App\Domain\Resume\Intelligence\DTO\RecommendationDTO;
use JobVisa\App\Domain\Resume\Intelligence\DTO\ResumeIntelligenceContext;
use JobVisa\App\Domain\Resume\Intelligence\Support\RoleSkillTaxonomy;

/**
 * Explainable keyword coverage vs role-expected terms (0–100).
 */
final class KeywordMatchingService
{
    /**
     * @return array{
     *   score: int,
     *   role: string,
     *   target_keywords: list<string>,
     *   matched: list<string>,
     *   missing: list<string>,
     *   resume_keywords: list<string>,
     *   recommendations: list<RecommendationDTO>
     * }
     */
    public function analyze(ResumeIntelligenceContext $context, ?string $targetRole = null): array
    {
        $role = RoleSkillTaxonomy::resolveRole($targetRole, $context->headline, $this->currentTitleHint($context));
        $targets = RoleSkillTaxonomy::expectedSkills($role);
        $resumeKeywords = $this->extractResumeKeywords($context);

        $matched = [];
        $missing = [];
        foreach ($targets as $keyword) {
            if ($this->present($keyword, $resumeKeywords, $context)) {
                $matched[] = $keyword;
            } else {
                $missing[] = $keyword;
            }
        }

        $total = count($targets);
        $score = $total > 0 ? (int) round((count($matched) / $total) * 100) : 0;
        $score = max(0, min(100, $score));

        $recs = [];
        if (count($missing) > 0) {
            $sample = array_slice($missing, 0, 5);
            $recs[] = new RecommendationDTO(
                'KW_MISSING',
                'Improve keyword coverage for your target role',
                'Your resume is missing role-relevant keywords such as: ' . implode(', ', $sample) . '. Add them naturally in skills or experience where true.',
                count($missing) >= 5 ? 'high' : 'medium',
                'skills',
                min(15, count($missing) * 2),
                $context->sectionUrl('skills'),
            );
        }

        return [
            'score' => $score,
            'role' => $role,
            'target_keywords' => $targets,
            'matched' => $matched,
            'missing' => $missing,
            'resume_keywords' => array_values(array_slice($resumeKeywords, 0, 40)),
            'recommendations' => $recs,
        ];
    }

    private function currentTitleHint(ResumeIntelligenceContext $context): string
    {
        foreach ($context->experience as $row) {
            $title = trim((string) ($row['job_title'] ?? ''));
            if ($title !== '') {
                return $title;
            }
        }

        return '';
    }

    /**
     * @return list<string>
     */
    private function extractResumeKeywords(ResumeIntelligenceContext $context): array
    {
        $parts = [$context->headline, $context->summary];
        foreach ($context->skills as $row) {
            $parts[] = (string) ($row['skill_name'] ?? $row['name'] ?? '');
        }
        foreach ($context->experience as $row) {
            $parts[] = (string) ($row['job_title'] ?? '');
            $parts[] = (string) ($row['responsibilities'] ?? $row['description'] ?? '');
            $parts[] = (string) ($row['achievements'] ?? '');
        }
        foreach ($context->projects as $row) {
            $parts[] = (string) ($row['title'] ?? '');
            $parts[] = (string) ($row['description'] ?? '');
            $parts[] = (string) ($row['technologies'] ?? $row['tech_stack'] ?? '');
        }

        $text = mb_strtolower(implode(' ', $parts));
        $text = preg_replace('/[^a-z0-9\/\+\.#\s-]/', ' ', $text) ?? $text;
        $tokens = preg_split('/\s+/', $text) ?: [];
        $out = [];
        foreach ($tokens as $token) {
            $token = trim($token, "-./");
            if (mb_strlen($token) >= 2) {
                $out[$token] = $token;
            }
        }

        return array_values($out);
    }

    /**
     * @param  list<string>  $resumeKeywords
     */
    private function present(string $keyword, array $resumeKeywords, ResumeIntelligenceContext $context): bool
    {
        $needle = mb_strtolower($keyword);
        foreach ($resumeKeywords as $kw) {
            if ($kw === $needle || str_contains($kw, $needle) || str_contains($needle, $kw)) {
                return true;
            }
        }

        $blob = mb_strtolower($context->headline . ' ' . $context->summary);
        foreach ($context->skills as $row) {
            $blob .= ' ' . mb_strtolower((string) ($row['skill_name'] ?? $row['name'] ?? ''));
        }

        return str_contains($blob, $needle);
    }
}

<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Intelligence\Support;

/**
 * Rule-based role → expected skills / keywords (explainable, non-discriminatory).
 * Not an AI black box — curated heuristics for Sprint 2F.2.
 */
final class RoleSkillTaxonomy
{
    /**
     * @return array<string, list<string>>
     */
    public static function catalog(): array
    {
        return [
            'software engineer' => ['php', 'javascript', 'sql', 'api', 'git', 'testing', 'laravel', 'rest', 'mysql', 'docker'],
            'web developer' => ['html', 'css', 'javascript', 'php', 'responsive', 'wordpress', 'react', 'vue', 'mysql'],
            'frontend developer' => ['javascript', 'typescript', 'react', 'vue', 'css', 'html', 'accessibility', 'webpack'],
            'backend developer' => ['php', 'java', 'node', 'api', 'sql', 'mysql', 'postgresql', 'redis', 'microservices'],
            'full stack' => ['javascript', 'php', 'react', 'api', 'sql', 'git', 'docker', 'rest'],
            'data analyst' => ['sql', 'excel', 'python', 'tableau', 'power bi', 'statistics', 'reporting', 'etl'],
            'project manager' => ['agile', 'scrum', 'jira', 'stakeholder', 'planning', 'risk', 'budget', 'communication'],
            'ui ux designer' => ['figma', 'wireframe', 'prototype', 'user research', 'accessibility', 'design system'],
            'devops engineer' => ['docker', 'kubernetes', 'ci/cd', 'aws', 'linux', 'terraform', 'monitoring', 'git'],
            'qa engineer' => ['testing', 'selenium', 'automation', 'api testing', 'bug tracking', 'regression'],
            'marketing' => ['seo', 'content', 'analytics', 'campaigns', 'social media', 'email marketing', 'branding'],
            'accountant' => ['accounting', 'excel', 'taxation', 'audit', 'financial reporting', 'quickbooks'],
            'general' => ['communication', 'teamwork', 'problem solving', 'microsoft office', 'time management'],
        ];
    }

    /**
     * Resolve best-matching role key from free text.
     */
    public static function resolveRole(?string $targetRole, string $headline = '', string $currentTitle = ''): string
    {
        $hay = mb_strtolower(trim(($targetRole ?? '') . ' ' . $headline . ' ' . $currentTitle));
        if ($hay === '') {
            return 'general';
        }

        $best = 'general';
        $bestScore = 0;
        foreach (array_keys(self::catalog()) as $role) {
            if ($role === 'general') {
                continue;
            }
            $score = 0;
            foreach (preg_split('/\s+/', $role) ?: [] as $part) {
                if ($part !== '' && str_contains($hay, $part)) {
                    $score++;
                }
            }
            if (str_contains($hay, $role)) {
                $score += 3;
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $role;
            }
        }

        return $bestScore > 0 ? $best : 'general';
    }

    /**
     * @return list<string>
     */
    public static function expectedSkills(string $roleKey): array
    {
        $catalog = self::catalog();

        return $catalog[$roleKey] ?? $catalog['general'];
    }
}

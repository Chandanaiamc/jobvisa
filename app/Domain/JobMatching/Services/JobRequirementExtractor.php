<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\JobMatching\Services;

use JobVisa\App\Domain\JobMatching\DTO\JobMatchContext;
use JobVisa\App\Domain\JobMatching\DTO\JobRequirementSignals;

/**
 * Deterministic extraction of job requirement signals from structured + free text.
 */
final class JobRequirementExtractor
{
    public function extract(JobMatchContext $context): JobRequirementSignals
    {
        $blob = mb_strtolower(trim(
            $context->jobTitle . "\n" . $context->jobRequirements . "\n" . $context->jobDescription
        ));

        $requiredSkills = [];
        $preferredSkills = [];
        foreach ($context->skillCatalogNames as $skill) {
            $needle = mb_strtolower($skill);
            if ($needle === '' || !str_contains($blob, $needle)) {
                continue;
            }
            if ($this->isPreferredContext($blob, $needle)) {
                $preferredSkills[$needle] = $skill;
            } else {
                $requiredSkills[$needle] = $skill;
            }
        }

        // Soft tokens from title words when catalog hits are empty
        if ($requiredSkills === [] && $preferredSkills === []) {
            foreach ($this->titleTokens($context->jobTitle) as $token) {
                $requiredSkills[$token] = $token;
            }
        }

        $requiredLangs = [];
        $preferredLangs = [];
        foreach ($context->languageCatalogNames as $lang) {
            $needle = mb_strtolower($lang);
            if ($needle === '' || !str_contains($blob, $needle)) {
                continue;
            }
            if ($this->isPreferredContext($blob, $needle)) {
                $preferredLangs[$needle] = $lang;
            } else {
                $requiredLangs[$needle] = $lang;
            }
        }

        $requiredCerts = [];
        $preferredCerts = [];
        foreach ($this->certificationLexicon() as $cert) {
            if (!str_contains($blob, $cert)) {
                continue;
            }
            if ($this->isPreferredContext($blob, $cert)) {
                $preferredCerts[$cert] = $cert;
            } else {
                $requiredCerts[$cert] = $cert;
            }
        }

        $inferredExp = $context->experienceMinYears;
        if ($inferredExp === null && preg_match('/(\d+)\s*\+?\s*years?/i', $blob, $m)) {
            $inferredExp = (int) $m[1];
        }

        $inferredEdu = $context->educationLevel;
        if ($inferredEdu === null || trim($inferredEdu) === '') {
            foreach (['bachelor', 'master', 'diploma', 'phd', 'degree', 'high school'] as $edu) {
                if (str_contains($blob, $edu)) {
                    $inferredEdu = $edu;
                    break;
                }
            }
        }

        $mentionsRemote = str_contains($blob, 'remote')
            || str_contains($blob, 'work from home')
            || str_contains($blob, 'wfh')
            || str_contains($context->jobTypeSlug, 'remote');

        return new JobRequirementSignals(
            requiredSkills: array_values($requiredSkills),
            preferredSkills: array_values(array_diff_key($preferredSkills, $requiredSkills)),
            requiredLanguages: array_values($requiredLangs),
            preferredLanguages: array_values(array_diff_key($preferredLangs, $requiredLangs)),
            requiredCertifications: array_values($requiredCerts),
            preferredCertifications: array_values(array_diff_key($preferredCerts, $requiredCerts)),
            mentionsRemote: $mentionsRemote,
            inferredMinExperience: $inferredExp,
            inferredEducation: $inferredEdu,
        );
    }

    private function isPreferredContext(string $blob, string $needle): bool
    {
        $pos = mb_strpos($blob, $needle);
        if ($pos === false) {
            return false;
        }
        $window = mb_substr($blob, max(0, $pos - 40), mb_strlen($needle) + 80);

        return (bool) preg_match('/\b(preferred|preferable|nice to have|optional|bonus|advantage)\b/i', $window);
    }

    /**
     * @return list<string>
     */
    private function titleTokens(string $title): array
    {
        $title = mb_strtolower($title);
        $title = preg_replace('/[^a-z0-9\s-]/', ' ', $title) ?? $title;
        $parts = preg_split('/\s+/', $title) ?: [];
        $stop = ['for', 'and', 'the', 'with', 'in', 'to', 'a', 'an', 'of', 'demo', 'dubai', 'uae', 'sri', 'lanka'];
        $out = [];
        foreach ($parts as $p) {
            $p = trim($p, '-');
            if (mb_strlen($p) < 3 || in_array($p, $stop, true)) {
                continue;
            }
            $out[$p] = $p;
        }

        return array_values($out);
    }

    /**
     * @return list<string>
     */
    private function certificationLexicon(): array
    {
        return [
            'nursing license',
            'license',
            'safety certification',
            'osha',
            'pmp',
            'aws',
            'cissp',
            'ielts',
            'toefl',
            'first aid',
            'cpr',
            'diploma',
        ];
    }
}

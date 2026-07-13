<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\RecruiterAssistant\Services;

use JobVisa\App\Domain\RecruiterAssistant\DTO\RecruiterSearchCriteria;

/**
 * Deterministic natural-language → structured recruiter filters.
 * No external AI APIs.
 */
final class NaturalLanguageQueryParser
{
    /**
     * @param  list<array{id: int, title: string}>  $ownedJobs
     * @param  list<string>  $skillCatalog
     */
    public function parse(string $query, array $ownedJobs = [], array $skillCatalog = []): RecruiterSearchCriteria
    {
        $raw = trim($query);
        $normalized = mb_strtolower($raw);
        $interpreted = [];

        $skills = $this->extractSkills($normalized, $skillCatalog);
        if ($skills !== []) {
            $interpreted[] = 'Skills: ' . implode(', ', $skills);
        }

        $minYears = null;
        if (preg_match('/(\d+)\s*\+?\s*years?(?:\s+of)?(?:\s+experience)?/i', $normalized, $m)
            || preg_match('/experience\s*(?:of\s*)?(\d+)/i', $normalized, $m)
            || preg_match('/at\s+least\s+(\d+)\s*years?/i', $normalized, $m)) {
            $minYears = max(0, min(40, (int) $m[1]));
            $interpreted[] = 'Min experience: ' . $minYears . ' years';
        }

        $education = [];
        foreach (['phd', 'doctorate', 'master', 'mba', 'bachelor', 'degree', 'diploma', 'high school', 'secondary'] as $edu) {
            if (str_contains($normalized, $edu)) {
                $education[] = $edu;
            }
        }
        $education = array_values(array_unique($education));
        if ($education !== []) {
            $interpreted[] = 'Education: ' . implode(', ', $education);
        }

        $certs = [];
        foreach (['nursing license', 'safety certification', 'osha', 'pmp', 'aws', 'cissp', 'ielts', 'toefl', 'first aid', 'cpr', 'license', 'certification'] as $cert) {
            if (str_contains($normalized, $cert)) {
                $certs[] = $cert;
            }
        }
        $certs = array_values(array_unique($certs));
        if ($certs !== []) {
            $interpreted[] = 'Certifications: ' . implode(', ', $certs);
        }

        $location = null;
        foreach (['dubai', 'abu dhabi', 'uae', 'qatar', 'saudi', 'sri lanka', 'colombo', 'remote'] as $place) {
            if (str_contains($normalized, $place)) {
                $location = $place;
                $interpreted[] = 'Location: ' . $place;
                break;
            }
        }

        $minMatch = null;
        if (preg_match('/(?:match|ai match|matching)\s*(?:score\s*)?(?:above|over|>=|at least)?\s*(\d{1,3})/i', $normalized, $m)
            || preg_match('/(?:above|over|>=)\s*(\d{1,3})\s*(?:%?\s*)?(?:match|ai match)/i', $normalized, $m)) {
            $minMatch = max(0, min(100, (int) $m[1]));
            $interpreted[] = 'Min AI match: ' . $minMatch;
        }

        $minRank = null;
        if (preg_match('/(?:rank(?:ing)?|overall)\s*(?:score\s*)?(?:above|over|>=|at least)?\s*(\d{1,3})/i', $normalized, $m)) {
            $minRank = max(0, min(100, (int) $m[1]));
            $interpreted[] = 'Min ranking score: ' . $minRank;
        }

        $interviewReady = (bool) preg_match('/\b(interview[- ]?ready|ready for interview|shortlist[- ]?ready)\b/i', $normalized);
        if ($interviewReady) {
            $interpreted[] = 'Interview-ready filter on';
            $minRank ??= 70;
            $minMatch ??= 55;
        }

        $jobId = null;
        foreach ($ownedJobs as $job) {
            $title = mb_strtolower((string) ($job['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            // Match distinctive tokens from job title in the query
            $tokens = preg_split('/\s+/', preg_replace('/[^a-z0-9\s]/', ' ', $title) ?? $title) ?: [];
            $hits = 0;
            foreach ($tokens as $token) {
                if (mb_strlen($token) < 4) {
                    continue;
                }
                if (str_contains($normalized, $token)) {
                    $hits++;
                }
            }
            if ($hits >= 2 || ($hits >= 1 && str_contains($normalized, 'for this job'))) {
                $jobId = (int) $job['id'];
                $interpreted[] = 'Scoped to job: ' . (string) $job['title'];
                break;
            }
        }

        if ($interpreted === []) {
            $interpreted[] = 'Broad candidate search (no strong filters detected)';
        }

        return new RecruiterSearchCriteria(
            rawQuery: $raw,
            skills: $skills,
            minExperienceYears: $minYears,
            educationKeywords: $education,
            certifications: $certs,
            location: $location,
            minMatchScore: $minMatch,
            minRankingScore: $minRank,
            jobId: $jobId,
            interviewReadyOnly: $interviewReady,
            interpreted: $interpreted,
        );
    }

    /**
     * @param  list<string>  $skillCatalog
     * @return list<string>
     */
    private function extractSkills(string $normalized, array $skillCatalog): array
    {
        $found = [];
        // Prefer longer catalog names first
        $catalog = $skillCatalog;
        usort($catalog, static fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));
        foreach ($catalog as $skill) {
            $needle = mb_strtolower(trim($skill));
            if ($needle === '' || mb_strlen($needle) < 2) {
                continue;
            }
            if (str_contains($normalized, $needle)) {
                $found[$needle] = $skill;
            }
        }

        // Soft tokens if catalog empty/misses
        if ($found === [] && preg_match_all('/\b(php|javascript|python|java|sql|nursing|hospitality|construction|safety|english|arabic|excel|react|laravel)\b/i', $normalized, $m)) {
            foreach ($m[1] as $token) {
                $t = mb_strtolower($token);
                $found[$t] = $t;
            }
        }

        return array_values($found);
    }
}

<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\ResumeBuilder\Services;

use JobVisa\App\Domain\ResumeBuilder\Support\ResumeBuilderVersion;

/**
 * Deterministic ATS-oriented resume content generator (no external AI).
 */
final class ResumeBuilderGenerator
{
    /**
     * @param  array<string, mixed>  $context
     * @return array{
     *   professional_summary: string,
     *   content: array<string, mixed>,
     *   ats_score: int,
     *   missing_keywords: list<string>,
     *   keyword_suggestions: list<array<string, mixed>>
     * }
     */
    public function generate(array $context): array
    {
        $targetRole = isset($context['target_role']) && is_string($context['target_role'])
            ? $context['target_role']
            : null;
        $headline = trim((string) ($context['headline'] ?? ''));
        $currentSummary = trim((string) ($context['summary'] ?? ''));
        $years = $context['years_experience'] ?? null;
        $skills = $this->stringList($context['skills'] ?? []);
        $experience = is_array($context['experience'] ?? null) ? $context['experience'] : [];
        $education = is_array($context['education'] ?? null) ? $context['education'] : [];
        $certs = is_array($context['certifications'] ?? null) ? $context['certifications'] : [];
        $jobKeywords = $this->stringList($context['job_keywords'] ?? []);
        $missingFromJobs = $this->stringList($context['missing_keywords'] ?? []);

        $summary = $this->professionalSummary($targetRole, $headline, $currentSummary, $years, $skills, $experience);
        $expRewrites = $this->experienceBullets($experience, $skills, $jobKeywords);
        $techSkills = $this->suggestTechnicalSkills($skills, $missingFromJobs, $jobKeywords, $targetRole);
        $softSkills = $this->suggestSoftSkills($skills, $targetRole);
        $eduImprovements = $this->educationImprovements($education, $targetRole);
        $certImprovements = $this->certificationImprovements($certs, $targetRole);
        $atsContent = $this->atsFriendlyDocument($summary, $expRewrites, $skills, $techSkills, $eduImprovements, $certImprovements, $targetRole);
        $missingKeywords = $this->detectMissingKeywords($skills, $jobKeywords, $missingFromJobs, $summary, $expRewrites);
        $keywordSuggestions = $this->keywordSuggestions($missingKeywords, $targetRole);
        $atsScore = $this->atsOptimizationScore($summary, $expRewrites, $skills, $techSkills, $missingKeywords, $education, $certs);

        return [
            'professional_summary' => $summary,
            'content' => [
                'experience_bullets' => $expRewrites,
                'suggested_technical_skills' => $techSkills,
                'suggested_soft_skills' => $softSkills,
                'education_improvements' => $eduImprovements,
                'certification_improvements' => $certImprovements,
                'ats_friendly_content' => $atsContent,
            ],
            'ats_score' => $atsScore,
            'missing_keywords' => $missingKeywords,
            'keyword_suggestions' => $keywordSuggestions,
        ];
    }

    /**
     * @param  list<string>  $skills
     * @param  list<array<string, mixed>>  $experience
     */
    private function professionalSummary(
        ?string $targetRole,
        string $headline,
        string $current,
        mixed $years,
        array $skills,
        array $experience,
    ): string {
        $role = $targetRole ?: ($headline !== '' ? $headline : 'professional');
        $y = $years !== null ? (float) $years : null;
        $topSkills = array_slice($skills, 0, 5);
        $skillPhrase = $topSkills !== [] ? implode(', ', $topSkills) : 'core domain competencies';
        $companies = [];
        foreach (array_slice($experience, 0, 2) as $row) {
            $c = trim((string) ($row['company_name'] ?? ''));
            if ($c !== '') {
                $companies[] = $c;
            }
        }
        $expPhrase = $companies !== []
            ? 'Experience includes work with ' . implode(' and ', $companies) . '.'
            : 'Focused on delivering measurable outcomes in prior roles.';

        $yearsPhrase = $y !== null && $y > 0
            ? sprintf('with approximately %.0f years of experience', $y)
            : 'with hands-on professional experience';

        $base = sprintf(
            'Results-driven %s %s in %s. %s Skilled in producing ATS-readable achievements that highlight impact, ownership and collaboration.',
            $role,
            $yearsPhrase,
            $skillPhrase,
            $expPhrase
        );

        if ($current !== '' && mb_strlen($current) >= 40) {
            // Preserve intent while rewriting into ATS-friendly structure
            $trimmed = preg_replace('/\s+/u', ' ', $current) ?? $current;
            $trimmed = mb_substr($trimmed, 0, 220);

            return $base . ' Background highlights: ' . rtrim($trimmed, '.') . '.';
        }

        return $base;
    }

    /**
     * @param  list<array<string, mixed>>  $experience
     * @param  list<string>  $skills
     * @param  list<string>  $jobKeywords
     * @return list<array<string, mixed>>
     */
    private function experienceBullets(array $experience, array $skills, array $jobKeywords): array
    {
        $out = [];
        $skillHint = $skills[0] ?? ($jobKeywords[0] ?? 'key responsibilities');
        foreach (array_slice($experience, 0, 6) as $row) {
            $id = (int) ($row['id'] ?? 0);
            $title = trim((string) ($row['job_title'] ?? 'Role'));
            $company = trim((string) ($row['company_name'] ?? 'Organization'));
            $raw = trim((string) ($row['achievements'] ?? $row['responsibilities'] ?? $row['description'] ?? ''));
            $bullets = $this->rewriteBullets($raw, $title, $company, $skillHint);
            $out[] = [
                'experience_id' => $id,
                'job_title' => $title,
                'company_name' => $company,
                'original' => $raw,
                'improved_bullets' => $bullets,
            ];
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    private function rewriteBullets(string $raw, string $title, string $company, string $skillHint): array
    {
        $parts = [];
        if ($raw !== '') {
            $split = preg_split('/[\r\n•|;]+/u', $raw) ?: [];
            foreach ($split as $p) {
                $p = trim($p);
                if (mb_strlen($p) >= 12) {
                    $parts[] = $p;
                }
            }
        }

        $improved = [];
        if ($parts === []) {
            $improved[] = 'Delivered ' . $title . ' outcomes at ' . $company . ' by applying ' . $skillHint . ' to improve quality and throughput.';
            $improved[] = 'Collaborated with cross-functional stakeholders to standardize workflows and document measurable results.';
            $improved[] = 'Maintained accurate records and communication standards suitable for ATS and interview review.';
        } else {
            foreach (array_slice($parts, 0, 4) as $p) {
                $clean = rtrim($p, '.');
                if (!preg_match('/^(Led|Delivered|Improved|Managed|Implemented|Reduced|Increased|Built|Designed|Supported|Coordinated)/i', $clean)) {
                    $clean = 'Delivered ' . lcfirst($clean);
                }
                if (!preg_match('/\d/', $clean)) {
                    $clean .= ' with measurable impact on team performance';
                }
                $improved[] = $clean . '.';
            }
        }

        return array_values(array_unique($improved));
    }

    /**
     * @param  list<string>  $skills
     * @param  list<string>  $missing
     * @param  list<string>  $jobKeywords
     * @return list<array{name: string, type: string, reason: string}>
     */
    private function suggestTechnicalSkills(array $skills, array $missing, array $jobKeywords, ?string $targetRole): array
    {
        $have = array_map(static fn (string $s): string => mb_strtolower($s), $skills);
        $candidates = array_merge($missing, array_slice($jobKeywords, 0, 8), $this->roleTechSkills($targetRole));
        $out = [];
        $seen = [];
        foreach ($candidates as $name) {
            $name = trim($name);
            if ($name === '' || mb_strlen($name) > 80) {
                continue;
            }
            $key = mb_strtolower($name);
            if (isset($seen[$key]) || in_array($key, $have, true)) {
                continue;
            }
            $seen[$key] = true;
            $out[] = [
                'name' => $name,
                'type' => 'technical',
                'reason' => 'Suggested to close keyword and job-match coverage gaps.',
            ];
            if (count($out) >= 6) {
                break;
            }
        }

        return $out;
    }

    /**
     * @param  list<string>  $skills
     * @return list<array{name: string, type: string, reason: string}>
     */
    private function suggestSoftSkills(array $skills, ?string $targetRole): array
    {
        $catalog = ['Communication', 'Teamwork', 'Problem solving', 'Adaptability', 'Time management', 'Leadership', 'Attention to detail', 'Stakeholder management'];
        $have = array_map(static fn (string $s): string => mb_strtolower($s), $skills);
        $out = [];
        foreach ($catalog as $name) {
            if (in_array(mb_strtolower($name), $have, true)) {
                continue;
            }
            $out[] = [
                'name' => $name,
                'type' => 'soft',
                'reason' => $targetRole
                    ? 'Soft skill commonly valued for ' . $targetRole . ' roles.'
                    : 'Soft skill that strengthens ATS and interview signals.',
            ];
            if (count($out) >= 5) {
                break;
            }
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $education
     * @return list<array<string, mixed>>
     */
    private function educationImprovements(array $education, ?string $targetRole): array
    {
        $out = [];
        foreach (array_slice($education, 0, 5) as $row) {
            $degree = trim((string) ($row['degree'] ?? ''));
            $field = trim((string) ($row['field_of_study'] ?? ''));
            $desc = trim((string) ($row['description'] ?? ''));
            $label = trim($degree . ($field !== '' ? ' in ' . $field : ''));
            if ($label === '') {
                $label = 'Education entry';
            }
            $improved = $desc !== ''
                ? 'Completed ' . $label . '. ' . rtrim($desc, '.') . '. Applied coursework to practical ' . ($targetRole ?: 'professional') . ' scenarios.'
                : 'Completed ' . $label . ' with emphasis on applied skills relevant to ' . ($targetRole ?: 'target roles') . '.';
            $out[] = [
                'education_id' => (int) ($row['id'] ?? 0),
                'label' => $label,
                'original' => $desc,
                'improved_description' => $improved,
            ];
        }
        if ($out === []) {
            $out[] = [
                'education_id' => 0,
                'label' => 'Education',
                'original' => '',
                'improved_description' => 'Add a concise education description that names the credential, field and one applied outcome for ATS filters.',
            ];
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $certs
     * @return list<array<string, mixed>>
     */
    private function certificationImprovements(array $certs, ?string $targetRole): array
    {
        $out = [];
        foreach (array_slice($certs, 0, 5) as $row) {
            $name = trim((string) ($row['name'] ?? 'Certification'));
            $desc = trim((string) ($row['description'] ?? $row['issuer'] ?? ''));
            $improved = $desc !== ''
                ? $name . ' — ' . rtrim($desc, '.') . '. Reinforces readiness for ' . ($targetRole ?: 'professional practice') . '.'
                : $name . ' credential validating practical competency for ' . ($targetRole ?: 'the target role') . '.';
            $out[] = [
                'certification_id' => (int) ($row['id'] ?? 0),
                'name' => $name,
                'original' => $desc,
                'improved_description' => $improved,
            ];
        }
        if ($out === []) {
            $out[] = [
                'certification_id' => 0,
                'name' => 'Certification',
                'original' => '',
                'improved_description' => 'Add one role-aligned certification with issuer and validity for stronger ATS credential filters.',
            ];
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $expRewrites
     * @param  list<string>  $skills
     * @param  list<array{name: string, type: string, reason: string}>  $techSkills
     * @param  list<array<string, mixed>>  $edu
     * @param  list<array<string, mixed>>  $certs
     */
    private function atsFriendlyDocument(
        string $summary,
        array $expRewrites,
        array $skills,
        array $techSkills,
        array $edu,
        array $certs,
        ?string $targetRole,
    ): string {
        $lines = [];
        $lines[] = 'PROFESSIONAL SUMMARY';
        $lines[] = $summary;
        $lines[] = '';
        $lines[] = 'SKILLS';
        $allSkills = array_values(array_unique(array_merge(
            $skills,
            array_map(static fn (array $s): string => $s['name'], $techSkills)
        )));
        $lines[] = implode(' · ', array_slice($allSkills, 0, 15));
        $lines[] = '';
        $lines[] = 'EXPERIENCE';
        foreach ($expRewrites as $exp) {
            $lines[] = strtoupper((string) ($exp['job_title'] ?? 'ROLE')) . ' — ' . (string) ($exp['company_name'] ?? '');
            foreach (($exp['improved_bullets'] ?? []) as $b) {
                $lines[] = '• ' . $b;
            }
            $lines[] = '';
        }
        $lines[] = 'EDUCATION';
        foreach ($edu as $e) {
            $lines[] = '• ' . (string) ($e['improved_description'] ?? $e['label'] ?? '');
        }
        $lines[] = '';
        $lines[] = 'CERTIFICATIONS';
        foreach ($certs as $c) {
            $lines[] = '• ' . (string) ($c['improved_description'] ?? $c['name'] ?? '');
        }
        if ($targetRole) {
            $lines[] = '';
            $lines[] = 'TARGET ROLE KEYWORDS: ' . $targetRole;
        }

        return implode("\n", $lines);
    }

    /**
     * @param  list<string>  $skills
     * @param  list<string>  $jobKeywords
     * @param  list<string>  $missingFromJobs
     * @param  list<array<string, mixed>>  $expRewrites
     * @return list<string>
     */
    private function detectMissingKeywords(
        array $skills,
        array $jobKeywords,
        array $missingFromJobs,
        string $summary,
        array $expRewrites,
    ): array {
        $hay = mb_strtolower($summary);
        foreach ($expRewrites as $exp) {
            foreach (($exp['improved_bullets'] ?? []) as $b) {
                $hay .= ' ' . mb_strtolower((string) $b);
            }
        }
        foreach ($skills as $s) {
            $hay .= ' ' . mb_strtolower($s);
        }

        $candidates = array_values(array_unique(array_merge($missingFromJobs, $jobKeywords)));
        $missing = [];
        foreach ($candidates as $kw) {
            $kw = trim($kw);
            if ($kw === '' || mb_strlen($kw) < 3) {
                continue;
            }
            if (!str_contains($hay, mb_strtolower($kw))) {
                $missing[] = $kw;
            }
            if (count($missing) >= 12) {
                break;
            }
        }

        return $missing;
    }

    /**
     * @param  list<string>  $missing
     * @return list<array<string, mixed>>
     */
    private function keywordSuggestions(array $missing, ?string $targetRole): array
    {
        $out = [];
        foreach (array_slice($missing, 0, 8) as $kw) {
            $out[] = [
                'keyword' => $kw,
                'action' => 'Add naturally into summary or a quantified experience bullet.',
                'placement' => 'summary_or_experience',
                'priority' => count($out) < 3 ? 'high' : 'medium',
                'why' => $targetRole
                    ? 'Appears in matched jobs / requirements for ' . $targetRole . '.'
                    : 'Appears in matched job requirements but is weak or missing on the resume.',
            ];
        }
        if ($out === []) {
            $out[] = [
                'keyword' => $targetRole ?: 'core role keywords',
                'action' => 'Mirror exact phrases from top matched job titles and required skills.',
                'placement' => 'summary',
                'priority' => 'low',
                'why' => 'No strong missing keywords detected from current matches.',
            ];
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $expRewrites
     * @param  list<string>  $skills
     * @param  list<array{name: string, type: string, reason: string}>  $techSkills
     * @param  list<string>  $missingKeywords
     * @param  list<array<string, mixed>>  $education
     * @param  list<array<string, mixed>>  $certs
     */
    private function atsOptimizationScore(
        string $summary,
        array $expRewrites,
        array $skills,
        array $techSkills,
        array $missingKeywords,
        array $education,
        array $certs,
    ): int {
        $score = 0;
        $len = mb_strlen($summary);
        if ($len >= 180) {
            $score += 20;
        } elseif ($len >= 100) {
            $score += 12;
        } elseif ($len >= 40) {
            $score += 6;
        }

        $bulletCount = 0;
        $measurable = 0;
        foreach ($expRewrites as $exp) {
            foreach (($exp['improved_bullets'] ?? []) as $b) {
                $bulletCount++;
                if (preg_match('/\d/', (string) $b)) {
                    $measurable++;
                }
            }
        }
        if ($bulletCount >= 6) {
            $score += 20;
        } elseif ($bulletCount >= 3) {
            $score += 12;
        } elseif ($bulletCount > 0) {
            $score += 6;
        }
        if ($measurable >= 3) {
            $score += 15;
        } elseif ($measurable >= 1) {
            $score += 8;
        }

        $skillTotal = count($skills) + count($techSkills);
        if ($skillTotal >= 8) {
            $score += 15;
        } elseif ($skillTotal >= 4) {
            $score += 10;
        } elseif ($skillTotal > 0) {
            $score += 5;
        }

        if (count($education) > 0) {
            $score += 10;
        }
        if (count($certs) > 0) {
            $score += 10;
        }

        $missingPenalty = min(20, count($missingKeywords) * 2);
        $score += max(0, 10 - $missingPenalty);

        return max(0, min(100, $score));
    }

    /**
     * @return list<string>
     */
    private function roleTechSkills(?string $targetRole): array
    {
        if ($targetRole === null) {
            return [];
        }
        $r = mb_strtolower($targetRole);
        if (str_contains($r, 'nurs')) {
            return ['Patient assessment', 'Medication administration', 'EHR documentation', 'Infection control'];
        }
        if (preg_match('/develop|engineer|software|php/', $r)) {
            return ['REST APIs', 'Unit testing', 'Git', 'SQL'];
        }
        if (str_contains($r, 'data')) {
            return ['SQL', 'Data visualization', 'ETL', 'Statistics'];
        }

        return [];
    }

    /**
     * @param  mixed  $value
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }
        $out = [];
        foreach ($value as $item) {
            if (is_string($item) && trim($item) !== '') {
                $out[] = trim($item);
            }
        }

        return array_values(array_unique($out));
    }
}

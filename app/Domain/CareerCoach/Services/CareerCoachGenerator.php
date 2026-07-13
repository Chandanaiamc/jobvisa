<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\CareerCoach\Services;

use JobVisa\App\Domain\CareerCoach\DTO\CareerCoachSessionDTO;
use JobVisa\App\Domain\CareerCoach\Support\CareerCoachVersion;

/**
 * Deterministic career coaching recommendations (no external AI).
 */
final class CareerCoachGenerator
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function generate(array $context): CareerCoachSessionDTO
    {
        $resumeId = (int) ($context['resume_id'] ?? 0);
        $userId = (int) ($context['user_id'] ?? 0);
        $targetRole = isset($context['target_role']) && is_string($context['target_role'])
            ? $context['target_role']
            : null;

        $scores = is_array($context['scores'] ?? null) ? $context['scores'] : [];
        $skills = $this->stringList($context['skills'] ?? []);
        $certs = $this->stringList($context['certifications'] ?? []);
        $projectCount = (int) ($context['project_count'] ?? 0);
        $achievementCount = (int) ($context['achievement_count'] ?? 0);
        $portfolioCount = (int) ($context['portfolio_count'] ?? 0);
        $years = isset($context['years_experience']) ? (int) $context['years_experience'] : null;
        $education = $this->stringList($context['education'] ?? []);
        $matches = is_array($context['job_matches'] ?? null) ? $context['job_matches'] : [];
        $intelGaps = is_array($context['intelligence_gaps'] ?? null) ? $context['intelligence_gaps'] : [];
        $matchMissing = $this->stringList($context['match_missing_skills'] ?? []);

        $skillGaps = $this->skillGaps($skills, $intelGaps, $matchMissing, $targetRole, $matches);
        $nextRoles = $this->nextRoles($skills, $matches, $targetRole, $scores);
        $roadmap = $this->learningRoadmap($skillGaps, $scores, $years);
        $certRecs = $this->certificationRecs($certs, $skillGaps, $nextRoles, $targetRole);
        $portfolioRecs = $this->portfolioRecs($projectCount, $achievementCount, $portfolioCount, $scores);
        $jobs = $this->jobOpportunities($matches);
        $summary = $this->summary($scores, $skillGaps, $nextRoles, $jobs, $targetRole);
        $headline = $this->headline($scores, $nextRoles, $targetRole);

        return new CareerCoachSessionDTO(
            $resumeId,
            $userId,
            $targetRole,
            $headline,
            $summary,
            $skillGaps,
            $nextRoles,
            $roadmap,
            $certRecs,
            $portfolioRecs,
            $jobs,
            $scores,
            CareerCoachVersion::CURRENT,
            date('Y-m-d H:i:s'),
            (bool) ($context['can_edit'] ?? false),
        );
    }

    /**
     * @param  list<string>  $skills
     * @param  list<array<string, mixed>>  $intelGaps
     * @param  list<string>  $matchMissing
     * @param  list<array<string, mixed>>  $matches
     * @return list<array<string, mixed>>
     */
    private function skillGaps(
        array $skills,
        array $intelGaps,
        array $matchMissing,
        ?string $targetRole,
        array $matches,
    ): array {
        $out = [];
        $seen = [];

        foreach ($intelGaps as $gap) {
            if (!is_array($gap)) {
                continue;
            }
            $label = trim((string) ($gap['skill'] ?? $gap['label'] ?? $gap['name'] ?? ''));
            if ($label === '' || isset($seen[mb_strtolower($label)])) {
                continue;
            }
            $seen[mb_strtolower($label)] = true;
            $out[] = [
                'skill' => $label,
                'priority' => 'high',
                'reason' => (string) ($gap['reason'] ?? $gap['message'] ?? 'Identified by resume intelligence skill-gap analysis.'),
                'source' => 'intelligence',
            ];
        }

        foreach ($matchMissing as $skill) {
            $key = mb_strtolower($skill);
            if ($skill === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = [
                'skill' => $skill,
                'priority' => 'high',
                'reason' => 'Frequently missing against jobs you match with.',
                'source' => 'job_match',
            ];
        }

        if ($targetRole !== null) {
            foreach ($this->roleSuggestedSkills($targetRole) as $skill) {
                $key = mb_strtolower($skill);
                if (isset($seen[$key]) || $this->hasSkill($skills, $skill)) {
                    continue;
                }
                $seen[$key] = true;
                $out[] = [
                    'skill' => $skill,
                    'priority' => 'medium',
                    'reason' => 'Common requirement for target role: ' . $targetRole . '.',
                    'source' => 'target_role',
                ];
            }
        }

        if ($out === [] && $matches !== []) {
            $out[] = [
                'skill' => 'Role-specific domain depth',
                'priority' => 'medium',
                'reason' => 'No hard skill gaps detected — deepen specialty examples tied to your top matches.',
                'source' => 'coach',
            ];
        }

        if ($out === []) {
            $out[] = [
                'skill' => 'Profile completeness',
                'priority' => 'low',
                'reason' => 'Add more skills and match more jobs to refine gap analysis.',
                'source' => 'coach',
            ];
        }

        usort($out, static function (array $a, array $b): int {
            $rank = ['high' => 0, 'medium' => 1, 'low' => 2];

            return ($rank[$a['priority']] ?? 9) <=> ($rank[$b['priority']] ?? 9);
        });

        return array_slice($out, 0, 8);
    }

    /**
     * @param  list<string>  $skills
     * @param  list<array<string, mixed>>  $matches
     * @param  array<string, mixed>  $scores
     * @return list<array<string, mixed>>
     */
    private function nextRoles(array $skills, array $matches, ?string $targetRole, array $scores): array
    {
        $roles = [];
        $seen = [];

        if ($targetRole !== null) {
            $roles[] = [
                'title' => $targetRole,
                'fit' => 'target',
                'confidence' => max(40, min(95, (int) ($scores['resume_overall'] ?? 50) + 10)),
                'why' => 'You set this as your coaching target role.',
            ];
            $seen[mb_strtolower($targetRole)] = true;
        }

        foreach (array_slice($matches, 0, 5) as $m) {
            $title = trim((string) ($m['job_title'] ?? $m['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $key = mb_strtolower($title);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $score = (int) ($m['overall_score'] ?? 0);
            $roles[] = [
                'title' => $title,
                'fit' => $score >= 70 ? 'strong' : ($score >= 50 ? 'good' : 'stretch'),
                'confidence' => max(20, min(95, $score)),
                'why' => 'Based on existing job-match score ' . $score . '/100.',
            ];
        }

        foreach ($this->inferRolesFromSkills($skills) as $inferred) {
            $key = mb_strtolower($inferred['title']);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $roles[] = $inferred;
        }

        if ($roles === []) {
            $roles[] = [
                'title' => 'Mid-level specialist in your primary skill area',
                'fit' => 'exploratory',
                'confidence' => 45,
                'why' => 'Add more skills or run job matches to sharpen next-role suggestions.',
            ];
        }

        return array_slice($roles, 0, 6);
    }

    /**
     * @param  list<array<string, mixed>>  $skillGaps
     * @param  array<string, mixed>  $scores
     * @return list<array<string, mixed>>
     */
    private function learningRoadmap(array $skillGaps, array $scores, ?int $years): array
    {
        $steps = [];
        $priority = 1;

        $resume = (int) ($scores['resume_overall'] ?? 0);
        if ($resume < 60) {
            $steps[] = [
                'priority' => $priority++,
                'horizon' => '0–30 days',
                'action' => 'Raise resume intelligence score above 60 by completing weak sections and measurable achievements.',
                'outcome' => 'Stronger baseline for role matching and employer readiness.',
            ];
        }

        foreach (array_slice($skillGaps, 0, 4) as $gap) {
            $skill = (string) ($gap['skill'] ?? 'priority skill');
            $steps[] = [
                'priority' => $priority++,
                'horizon' => $priority <= 3 ? '0–30 days' : '30–90 days',
                'action' => 'Build practical evidence for: ' . $skill . '.',
                'outcome' => 'Close a high-impact skill gap employers already signal.',
            ];
        }

        $steps[] = [
            'priority' => $priority++,
            'horizon' => '30–90 days',
            'action' => 'Complete one portfolio project or case study aligned to your top next-best role.',
            'outcome' => 'Proof of applied skill beyond CV claims.',
        ];

        if ($years !== null && $years < 3) {
            $steps[] = [
                'priority' => $priority++,
                'horizon' => '90–180 days',
                'action' => 'Seek stretch responsibilities or mentorship to deepen experience narratives.',
                'outcome' => 'Improve experience-dimension competitiveness.',
            ];
        } else {
            $steps[] = [
                'priority' => $priority++,
                'horizon' => '90–180 days',
                'action' => 'Target a leadership or specialization certification matched to your next role.',
                'outcome' => 'Signal seniority and domain credibility.',
            ];
        }

        return array_slice($steps, 0, 8);
    }

    /**
     * @param  list<string>  $certs
     * @param  list<array<string, mixed>>  $skillGaps
     * @param  list<array<string, mixed>>  $nextRoles
     * @return list<array<string, mixed>>
     */
    private function certificationRecs(array $certs, array $skillGaps, array $nextRoles, ?string $targetRole): array
    {
        $recs = [];
        $blob = mb_strtolower(implode(' ', array_merge(
            $certs,
            array_map(static fn (array $g): string => (string) ($g['skill'] ?? ''), $skillGaps),
            array_map(static fn (array $r): string => (string) ($r['title'] ?? ''), $nextRoles),
            [$targetRole ?? '']
        )));

        $catalog = [
            ['needle' => 'nurs', 'name' => 'Registered Nurse license / DHA or equivalent', 'why' => 'Clinical credential often required for Gulf nursing roles.'],
            ['needle' => 'nurs', 'name' => 'BLS / ACLS', 'why' => 'Strengthens acute-care readiness for hospital interviews.'],
            ['needle' => 'softwar|develop|php|java|python', 'name' => 'Cloud fundamentals (AWS / Azure Fundamentals)', 'why' => 'Common baseline for modern engineering roles.'],
            ['needle' => 'softwar|develop|php|java|python', 'name' => 'Security awareness or OWASP-oriented cert', 'why' => 'Signals secure coding maturity to employers.'],
            ['needle' => 'project|manag', 'name' => 'CAPM or PMP (or PRINCE2 Foundation)', 'why' => 'Supports project coordination career paths.'],
            ['needle' => 'data|analy', 'name' => 'Google Data Analytics or SQL-focused certificate', 'why' => 'Closes analytics skill gaps with portable proof.'],
            ['needle' => 'market', 'name' => 'Google Digital Marketing / Meta Blueprint', 'why' => 'Adds campaign credibility for marketing roles.'],
        ];

        $have = array_map(static fn (string $c): string => mb_strtolower($c), $certs);
        foreach ($catalog as $item) {
            if (!preg_match('/' . $item['needle'] . '/i', $blob)) {
                continue;
            }
            $skip = false;
            foreach ($have as $h) {
                if (str_contains($h, mb_strtolower(mb_substr($item['name'], 0, 12)))) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) {
                continue;
            }
            $recs[] = [
                'name' => $item['name'],
                'priority' => count($recs) < 2 ? 'high' : 'medium',
                'why' => $item['why'],
            ];
            if (count($recs) >= 4) {
                break;
            }
        }

        if ($recs === []) {
            $recs[] = [
                'name' => 'Role-aligned professional certificate',
                'priority' => 'medium',
                'why' => 'Pick one credential that maps directly to your top next-best role and list it on your resume.',
            ];
        }

        return $recs;
    }

    /**
     * @param  array<string, mixed>  $scores
     * @return list<array<string, mixed>>
     */
    private function portfolioRecs(int $projects, int $achievements, int $portfolio, array $scores): array
    {
        $recs = [];
        if ($portfolio < 1) {
            $recs[] = [
                'action' => 'Add at least one portfolio item with outcomes and links.',
                'priority' => 'high',
                'why' => 'Employers need proof beyond claims on the resume.',
            ];
        }
        if ($projects < 2) {
            $recs[] = [
                'action' => 'Document 2+ projects with problem, approach, and measurable result.',
                'priority' => 'high',
                'why' => 'Projects close evidence gaps for skills and certifications.',
            ];
        }
        if ($achievements < 2) {
            $recs[] = [
                'action' => 'Add quantified achievements (%, time, volume, quality).',
                'priority' => 'medium',
                'why' => 'Measurable wins raise employer readiness and interview credibility.',
            ];
        }
        if ((int) ($scores['employer_readiness'] ?? 0) < 55) {
            $recs[] = [
                'action' => 'Tighten professional summary and headline toward your next-best role.',
                'priority' => 'medium',
                'why' => 'Employer readiness score is below a comfortable bar.',
            ];
        }
        if ($recs === []) {
            $recs[] = [
                'action' => 'Refresh portfolio media and pin your strongest case study first.',
                'priority' => 'low',
                'why' => 'Your evidence volume looks healthy — optimize presentation and clarity.',
            ];
        }

        return array_slice($recs, 0, 5);
    }

    /**
     * @param  list<array<string, mixed>>  $matches
     * @return list<array<string, mixed>>
     */
    private function jobOpportunities(array $matches): array
    {
        $out = [];
        foreach (array_slice($matches, 0, 8) as $m) {
            $out[] = [
                'job_id' => (int) ($m['job_id'] ?? 0),
                'title' => (string) ($m['job_title'] ?? $m['title'] ?? ''),
                'country' => (string) ($m['country_name'] ?? ''),
                'match_score' => (int) ($m['overall_score'] ?? 0),
                'skills_score' => (int) ($m['skills_score'] ?? 0),
                'why' => 'Suitable based on cached job-match score.',
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $scores
     * @param  list<array<string, mixed>>  $skillGaps
     * @param  list<array<string, mixed>>  $nextRoles
     * @param  list<array<string, mixed>>  $jobs
     * @return array<string, mixed>
     */
    private function summary(
        array $scores,
        array $skillGaps,
        array $nextRoles,
        array $jobs,
        ?string $targetRole,
    ): array {
        $topRole = (string) (($nextRoles[0]['title'] ?? null) ?: ($targetRole ?? 'your next role'));

        return [
            'readiness_label' => $this->readinessLabel((int) ($scores['resume_overall'] ?? 0)),
            'focus' => 'Prioritize closing ' . count($skillGaps) . ' skill gap(s) while pursuing ' . $topRole . '.',
            'job_signal' => count($jobs) > 0
                ? 'You have ' . count($jobs) . ' suitable matched job opportunity signal(s).'
                : 'Run job matches to surface suitable opportunities.',
            'next_step' => (string) (($skillGaps[0]['skill'] ?? null)
                ? 'Start learning roadmap with: ' . $skillGaps[0]['skill']
                : 'Complete a portfolio project aligned to your next-best role.'),
        ];
    }

    /**
     * @param  array<string, mixed>  $scores
     * @param  list<array<string, mixed>>  $nextRoles
     */
    private function headline(array $scores, array $nextRoles, ?string $targetRole): string
    {
        $role = (string) (($nextRoles[0]['title'] ?? null) ?: ($targetRole ?? 'your next opportunity'));
        $overall = (int) ($scores['resume_overall'] ?? 0);

        return 'Career coach for ' . $role . ' · resume score ' . $overall . '/100';
    }

    private function readinessLabel(int $score): string
    {
        return match (true) {
            $score >= 75 => 'Strong',
            $score >= 55 => 'Developing',
            $score >= 35 => 'Emerging',
            default => 'Foundation',
        };
    }

    /**
     * @return list<string>
     */
    private function roleSuggestedSkills(string $role): array
    {
        $r = mb_strtolower($role);
        if (str_contains($r, 'nurs')) {
            return ['Patient assessment', 'Medication administration', 'Electronic health records', 'Infection control'];
        }
        if (preg_match('/develop|engineer|software|php|full.?stack/', $r)) {
            return ['System design basics', 'Automated testing', 'CI/CD', 'API design'];
        }
        if (str_contains($r, 'data')) {
            return ['SQL', 'Data visualization', 'Statistics fundamentals', 'ETL basics'];
        }
        if (str_contains($r, 'market')) {
            return ['Campaign analytics', 'SEO basics', 'Content strategy', 'CRM tools'];
        }

        return ['Communication', 'Stakeholder management', 'Domain tooling'];
    }

    /**
     * @param  list<string>  $skills
     * @return list<array<string, mixed>>
     */
    private function inferRolesFromSkills(array $skills): array
    {
        $blob = mb_strtolower(implode(' ', $skills));
        $out = [];
        if (preg_match('/nurs|clinical|patient/', $blob)) {
            $out[] = [
                'title' => 'Registered Nurse / Clinical Care Specialist',
                'fit' => 'inferred',
                'confidence' => 60,
                'why' => 'Inferred from clinical skills on your resume.',
            ];
        }
        if (preg_match('/php|laravel|javascript|python|java|react/', $blob)) {
            $out[] = [
                'title' => 'Software Developer',
                'fit' => 'inferred',
                'confidence' => 58,
                'why' => 'Inferred from technical skills on your resume.',
            ];
        }
        if (preg_match('/sql|excel|analytics|tableau|power bi/', $blob)) {
            $out[] = [
                'title' => 'Data Analyst',
                'fit' => 'inferred',
                'confidence' => 55,
                'why' => 'Inferred from analytics-related skills.',
            ];
        }

        return $out;
    }

    /**
     * @param  list<string>  $skills
     */
    private function hasSkill(array $skills, string $needle): bool
    {
        $n = mb_strtolower($needle);
        foreach ($skills as $s) {
            if (str_contains(mb_strtolower($s), $n) || str_contains($n, mb_strtolower($s))) {
                return true;
            }
        }

        return false;
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

<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\JobSearchCopilot\Services;

use JobVisa\App\Domain\JobSearchCopilot\DTO\JobSearchCopilotPlanDTO;
use JobVisa\App\Domain\JobSearchCopilot\Support\JobSearchCopilotVersion;

/**
 * Deterministic job search strategy + recommendations — no external AI APIs.
 */
final class JobSearchCopilotAnalyzer
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function generate(int $resumeId, int $userId, array $context): JobSearchCopilotPlanDTO
    {
        $goal = trim((string) ($context['career_goal'] ?? 'Career advancement'));
        if ($goal === '') {
            $goal = 'Career advancement';
        }

        $skills = $this->stringList($context['resume_skills'] ?? []);
        $missing = $this->stringList($context['missing_skills'] ?? []);
        $jobs = is_array($context['jobs'] ?? null) ? $context['jobs'] : [];
        $matchSnapshots = is_array($context['match_snapshots'] ?? null) ? $context['match_snapshots'] : [];

        $queries = $this->searchQueries($goal, $skills, $missing);
        $filters = $this->recommendedFilters($context, $goal, $skills);
        $recommendations = $this->rankJobs($jobs, $matchSnapshots, $skills, $goal, $context);
        $priority = $this->priorityOrder($recommendations);
        $weekly = $this->weeklyPlan($recommendations, $goal);
        $alerts = $this->alertKeywords($goal, $skills, $missing);
        $tips = $this->strategyTips($context, $recommendations);
        $score = $this->copilotScore($context, $recommendations);

        $plan = [
            'headline' => 'Job search plan for: ' . $goal,
            'summary' => sprintf(
                'Copilot score %d/100 with %d ranked opportunities across safe-fit, stretch and hidden-gem tracks.',
                $score,
                count($recommendations)
            ),
            'search_queries' => $queries,
            'recommended_filters' => $filters,
            'recommendations' => $recommendations,
            'safe_fits' => array_values(array_filter($recommendations, static fn (array $r): bool => ($r['category'] ?? '') === 'safe_fit')),
            'stretch_roles' => array_values(array_filter($recommendations, static fn (array $r): bool => ($r['category'] ?? '') === 'stretch')),
            'hidden_gems' => array_values(array_filter($recommendations, static fn (array $r): bool => ($r['category'] ?? '') === 'hidden_gem')),
            'application_priority' => $priority,
            'weekly_search_plan' => $weekly,
            'alert_keywords' => $alerts,
            'strategy_tips' => $tips,
            'signals' => [
                'resume_overall' => (int) ($context['resume_overall'] ?? 0),
                'gap_percentage' => (int) ($context['gap_percentage'] ?? 0),
                'salary_mid' => (int) ($context['salary_mid'] ?? 0),
                'portfolio_strength' => (int) ($context['portfolio_strength'] ?? 0),
                'published_jobs_scanned' => count($jobs),
            ],
        ];

        return new JobSearchCopilotPlanDTO(
            0,
            $resumeId,
            $userId,
            $goal,
            $score,
            count($recommendations),
            $plan,
            JobSearchCopilotVersion::CURRENT,
            '',
        );
    }

    /**
     * @param  list<string>  $skills
     * @param  list<string>  $missing
     * @return list<string>
     */
    private function searchQueries(string $goal, array $skills, array $missing): array
    {
        $primary = $skills[0] ?? 'professional';
        $gap = $missing[0] ?? '';
        $queries = [
            $goal,
            $goal . ' ' . $primary,
            trim($goal . ' remote OR hybrid'),
        ];
        if ($gap !== '') {
            $queries[] = $goal . ' ' . $gap . ' trainee OR junior';
        }
        $queries[] = $primary . ' ' . $this->seniorityHint($goal) . ' jobs';

        return array_values(array_unique(array_filter($queries)));
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  list<string>  $skills
     * @return array<string, mixed>
     */
    private function recommendedFilters(array $context, string $goal, array $skills): array
    {
        $years = (float) ($context['years'] ?? 0);
        $salaryMid = (int) ($context['salary_mid'] ?? 0);

        return [
            'keywords' => array_slice($skills, 0, 5),
            'experience_band' => match (true) {
                $years < 1 => '0–1 years',
                $years < 3 => '1–3 years',
                $years < 7 => '3–7 years',
                default => '7+ years',
            },
            'seniority' => $this->seniorityHint($goal),
            'preferred_locations' => $this->stringList($context['preferred_locations'] ?? ['Sri Lanka', 'Remote', 'Gulf']),
            'job_types' => ['Full-time', 'Contract', 'Remote'],
            'salary_floor_hint' => $salaryMid > 0 ? (int) round($salaryMid * 0.85) : null,
            'exclude_keywords' => ['unpaid', 'commission only'],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $jobs
     * @param  list<array<string, mixed>>  $snapshots
     * @param  list<string>  $skills
     * @param  array<string, mixed>  $context
     * @return list<array<string, mixed>>
     */
    private function rankJobs(array $jobs, array $snapshots, array $skills, string $goal, array $context): array
    {
        $snapByJob = [];
        foreach ($snapshots as $snap) {
            if (!is_array($snap)) {
                continue;
            }
            $jid = (int) ($snap['job_id'] ?? 0);
            if ($jid > 0) {
                $snapByJob[$jid] = $snap;
            }
        }

        $skillLower = array_map(static fn (string $s): string => mb_strtolower($s), $skills);
        $goalLower = mb_strtolower($goal);
        $ranked = [];

        foreach ($jobs as $job) {
            if (!is_array($job)) {
                continue;
            }
            $jobId = (int) ($job['id'] ?? 0);
            if ($jobId < 1) {
                continue;
            }

            $title = (string) ($job['title'] ?? '');
            $hay = mb_strtolower(
                $title . ' ' . (string) ($job['description'] ?? '') . ' ' . (string) ($job['requirements'] ?? '')
            );
            $matchScore = isset($snapByJob[$jobId])
                ? (int) ($snapByJob[$jobId]['overall_score'] ?? 0)
                : 0;

            $overlap = 0;
            foreach ($skillLower as $sk) {
                if ($sk !== '' && str_contains($hay, $sk)) {
                    $overlap++;
                }
            }
            $titleBoost = 0;
            foreach (preg_split('/\s+/u', $goalLower) ?: [] as $token) {
                if (mb_strlen($token) >= 4 && str_contains(mb_strtolower($title), $token)) {
                    $titleBoost += 8;
                }
            }

            $heuristic = min(100, ($overlap * 12) + $titleBoost + ((int) ($context['resume_overall'] ?? 0) > 60 ? 10 : 0));
            $score = $matchScore > 0
                ? (int) round(($matchScore * 0.7) + ($heuristic * 0.3))
                : $heuristic;

            $category = match (true) {
                $score >= 70 => 'safe_fit',
                $score >= 45 => 'stretch',
                default => 'hidden_gem',
            };

            $reasons = [];
            if ($matchScore > 0) {
                $reasons[] = 'Existing match score ' . $matchScore . '/100';
            }
            if ($overlap > 0) {
                $reasons[] = $overlap . ' resume skill(s) appear in the posting';
            }
            if ($titleBoost > 0) {
                $reasons[] = 'Title aligns with career goal keywords';
            }
            if ($reasons === []) {
                $reasons[] = 'Exploratory listing — useful for market scanning';
            }

            $ranked[] = [
                'job_id' => $jobId,
                'title' => $title,
                'country' => (string) ($job['country_name'] ?? ''),
                'job_type' => (string) ($job['job_type_name'] ?? ''),
                'score' => $score,
                'category' => $category,
                'reasons' => $reasons,
                'apply_urgency' => $score >= 75 ? 'high' : ($score >= 50 ? 'medium' : 'low'),
            ];
        }

        usort($ranked, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);
        $ranked = array_slice($ranked, 0, 12);

        foreach ($ranked as $i => &$row) {
            $row['priority'] = $i + 1;
        }
        unset($row);

        return $ranked;
    }

    /**
     * @param  list<array<string, mixed>>  $recommendations
     * @return list<array<string, mixed>>
     */
    private function priorityOrder(array $recommendations): array
    {
        $out = [];
        foreach ($recommendations as $r) {
            $out[] = [
                'priority' => (int) ($r['priority'] ?? 0),
                'job_id' => (int) ($r['job_id'] ?? 0),
                'title' => (string) ($r['title'] ?? ''),
                'category' => (string) ($r['category'] ?? ''),
                'score' => (int) ($r['score'] ?? 0),
                'apply_urgency' => (string) ($r['apply_urgency'] ?? 'low'),
            ];
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $recommendations
     * @return list<array<string, mixed>>
     */
    private function weeklyPlan(array $recommendations, string $goal): array
    {
        $top = array_slice($recommendations, 0, 5);

        return [
            [
                'day' => 'Monday',
                'focus' => 'Refresh search queries for ' . $goal,
                'actions' => ['Run primary query', 'Save 3 new listings'],
            ],
            [
                'day' => 'Tuesday',
                'focus' => 'Apply to safe-fit roles',
                'actions' => array_map(
                    static fn (array $r): string => 'Prepare application for: ' . (string) ($r['title'] ?? 'role'),
                    array_slice(array_values(array_filter($top, static fn (array $r): bool => ($r['category'] ?? '') === 'safe_fit')), 0, 2)
                ) ?: ['Shortlist 2 safe-fit postings'],
            ],
            [
                'day' => 'Wednesday',
                'focus' => 'Stretch roles + skill proof',
                'actions' => ['Tailor one cover letter', 'Update one portfolio proof point'],
            ],
            [
                'day' => 'Thursday',
                'focus' => 'Hidden gems & alerts',
                'actions' => ['Review alert keywords', 'Message one recruiter/network contact'],
            ],
            [
                'day' => 'Friday',
                'focus' => 'Pipeline review',
                'actions' => ['Recalculate copilot plan', 'Track follow-ups'],
            ],
        ];
    }

    /**
     * @param  list<string>  $skills
     * @param  list<string>  $missing
     * @return list<string>
     */
    private function alertKeywords(string $goal, array $skills, array $missing): array
    {
        $keys = [$goal];
        foreach (array_slice($skills, 0, 4) as $s) {
            $keys[] = $s;
        }
        foreach (array_slice($missing, 0, 2) as $m) {
            $keys[] = $m;
        }
        $keys[] = 'visa sponsorship';
        $keys[] = 'remote';

        return array_values(array_unique(array_filter(array_map('trim', $keys))));
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  list<array<string, mixed>>  $recommendations
     * @return list<string>
     */
    private function strategyTips(array $context, array $recommendations): array
    {
        $tips = [];
        $tips[] = 'Prioritize high-urgency safe-fit roles first, then schedule stretch applications mid-week.';
        if ((int) ($context['gap_percentage'] ?? 0) >= 35) {
            $tips[] = 'Skill-gap signals are elevated — pair applications with one learning milestone per week.';
        }
        if ((int) ($context['portfolio_strength'] ?? 0) >= 50) {
            $tips[] = 'Lead applications with portfolio proof points for technical screenings.';
        }
        if (count($recommendations) < 3) {
            $tips[] = 'Few published matches — broaden keywords and include adjacent job titles.';
        } else {
            $tips[] = 'Keep a rolling shortlist of 5–8 active opportunities; archive low-urgency gems.';
        }
        if ((int) ($context['salary_mid'] ?? 0) > 0) {
            $tips[] = 'Use your salary intelligence mid-point as a negotiation floor, not the opening ask.';
        }

        return $tips;
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  list<array<string, mixed>>  $recommendations
     */
    private function copilotScore(array $context, array $recommendations): int
    {
        $resume = (int) ($context['resume_overall'] ?? 0);
        $avg = 0;
        if ($recommendations !== []) {
            $avg = (int) round(array_sum(array_map(
                static fn (array $r): int => (int) ($r['score'] ?? 0),
                $recommendations
            )) / count($recommendations));
        }
        $volume = min(20, count($recommendations) * 2);
        $gapPenalty = min(15, (int) round(((int) ($context['gap_percentage'] ?? 0)) / 5));
        $portfolio = min(15, (int) round(((int) ($context['portfolio_strength'] ?? 0)) / 7));

        return max(0, min(100, (int) round(($resume * 0.25) + ($avg * 0.45) + $volume + $portfolio - $gapPenalty + 10)));
    }

    private function seniorityHint(string $goal): string
    {
        $g = mb_strtolower($goal);
        if (str_contains($g, 'senior') || str_contains($g, 'lead')) {
            return 'Senior';
        }
        if (str_contains($g, 'intern') || str_contains($g, 'junior') || str_contains($g, 'entry')) {
            return 'Junior';
        }

        return 'Mid';
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }
        $out = [];
        foreach ($value as $item) {
            $s = trim((string) $item);
            if ($s !== '') {
                $out[] = $s;
            }
        }

        return array_values(array_unique($out));
    }
}

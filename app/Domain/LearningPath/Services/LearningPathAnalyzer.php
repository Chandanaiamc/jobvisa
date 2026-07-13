<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\LearningPath\Services;

use JobVisa\App\Domain\LearningPath\DTO\LearningPathDTO;
use JobVisa\App\Domain\LearningPath\Support\LearningPathVersion;

/**
 * Deterministic personalized learning path builder — no external AI APIs.
 */
final class LearningPathAnalyzer
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function generate(int $resumeId, int $userId, array $context): LearningPathDTO
    {
        $goal = trim((string) ($context['career_goal'] ?? 'Career advancement'));
        if ($goal === '') {
            $goal = 'Career advancement';
        }

        $missing = $this->stringList($context['missing_skills'] ?? []);
        $matched = $this->stringList($context['matched_skills'] ?? []);
        $priority = is_array($context['priority_learning'] ?? null) ? $context['priority_learning'] : [];
        $skills = $this->stringList($context['resume_skills'] ?? []);

        if ($missing === [] && $priority !== []) {
            foreach ($priority as $row) {
                if (is_array($row) && trim((string) ($row['skill'] ?? '')) !== '') {
                    $missing[] = trim((string) $row['skill']);
                }
            }
            $missing = array_values(array_unique($missing));
        }
        if ($missing === []) {
            $missing = ['Professional communication', 'Domain tools mastery', 'Interview case practice'];
        }

        $levels = $this->levels($missing, $matched, $skills);
        $weekly = $this->weeklySchedule($levels);
        $timelineWeeks = max(4, count($weekly));
        $prioritySequence = $this->prioritySequence($missing);
        $courses = $this->courses($missing);
        $certs = $this->certifications($missing, $goal);
        $books = $this->books($missing, $goal);
        $youtube = $this->youtube($missing);
        $projects = $this->projects($missing, $goal);
        $portfolio = $this->portfolio($missing, $goal);
        $milestones = $this->milestones($levels, $projects);
        $alignment = $this->alignmentScore($context, $goal);

        $path = [
            'headline' => 'Personalized path toward: ' . $goal,
            'summary' => sprintf(
                'A %d-week path from beginner foundations to advanced practice, aligned to your skill gaps, salary signals and career goal.',
                $timelineWeeks
            ),
            'levels' => $levels,
            'weekly_schedule' => $weekly,
            'priority_sequence' => $prioritySequence,
            'courses' => $courses,
            'certifications' => $certs,
            'books' => $books,
            'youtube' => $youtube,
            'practice_projects' => $projects,
            'portfolio' => $portfolio,
            'milestones' => $milestones,
            'career_goal_alignment' => [
                'goal' => $goal,
                'score' => $alignment,
                'notes' => $this->alignmentNotes($context, $goal, $alignment),
            ],
            'signals' => [
                'gap_percentage' => (int) ($context['gap_percentage'] ?? 0),
                'readiness_score' => (int) ($context['readiness_score'] ?? 0),
                'salary_target' => (float) ($context['salary_target'] ?? 0),
                'salary_currency' => (string) ($context['salary_currency'] ?? ''),
                'coach_target_role' => (string) ($context['coach_target_role'] ?? ''),
            ],
        ];

        return new LearningPathDTO(
            0,
            $resumeId,
            $userId,
            isset($context['job_id']) && (int) $context['job_id'] > 0 ? (int) $context['job_id'] : null,
            $goal,
            $timelineWeeks,
            0,
            count($milestones),
            0,
            $alignment,
            $path,
            LearningPathVersion::CURRENT,
            '',
        );
    }

    /**
     * @param  array<string, mixed>  $pathJson
     * @return array{path_json: array<string, mixed>, progress_percent: int, milestones_total: int, milestones_done: int}
     */
    public function applyMilestone(array $pathJson, string $milestoneKey, bool $done): array
    {
        $milestones = is_array($pathJson['milestones'] ?? null) ? $pathJson['milestones'] : [];
        $found = false;
        foreach ($milestones as $i => $m) {
            if (!is_array($m)) {
                continue;
            }
            if ((string) ($m['key'] ?? '') === $milestoneKey) {
                $milestones[$i]['done'] = $done;
                $found = true;
            }
        }
        if (!$found) {
            return [
                'path_json' => $pathJson,
                'progress_percent' => 0,
                'milestones_total' => count($milestones),
                'milestones_done' => 0,
                'found' => false,
            ];
        }
        $pathJson['milestones'] = $milestones;
        $total = count($milestones);
        $doneCount = 0;
        foreach ($milestones as $m) {
            if (is_array($m) && !empty($m['done'])) {
                $doneCount++;
            }
        }
        $progress = $total > 0 ? (int) round(($doneCount / $total) * 100) : 0;

        return [
            'path_json' => $pathJson,
            'progress_percent' => $progress,
            'milestones_total' => $total,
            'milestones_done' => $doneCount,
            'found' => true,
        ];
    }

    /**
     * @param  list<string>  $missing
     * @param  list<string>  $matched
     * @param  list<string>  $skills
     * @return array<string, array{title: string, focus: list<string>, outcomes: list<string>}>
     */
    private function levels(array $missing, array $matched, array $skills): array
    {
        $beginner = array_slice($missing, 0, 2);
        if ($beginner === []) {
            $beginner = ['Core terminology', 'Tooling setup'];
        }
        $intermediate = array_slice($missing, 2, 3);
        if ($intermediate === []) {
            $intermediate = array_slice(array_merge($missing, $matched), 0, 3);
        }
        $advanced = array_slice($missing, 5, 3);
        if ($advanced === []) {
            $advanced = ['System design thinking', 'Leadership communication', 'Interview simulations'];
        }

        return [
            'beginner' => [
                'title' => 'Beginner',
                'focus' => array_values(array_unique($beginner)),
                'outcomes' => ['Build foundations', 'Complete first guided tutorial', 'Create notes library'],
            ],
            'intermediate' => [
                'title' => 'Intermediate',
                'focus' => array_values(array_unique(array_filter($intermediate))),
                'outcomes' => ['Ship a small project', 'Earn one credential', 'Document measurable results'],
            ],
            'advanced' => [
                'title' => 'Advanced',
                'focus' => array_values(array_unique($advanced)),
                'outcomes' => ['Complete portfolio case study', 'Mock interviews', 'Apply with evidence-backed story'],
            ],
        ];
    }

    /**
     * @param  array<string, array{title: string, focus: list<string>, outcomes: list<string>}>  $levels
     * @return list<array{week: int, level: string, theme: string, hours: int, tasks: list<string>}>
     */
    private function weeklySchedule(array $levels): array
    {
        $schedule = [];
        $week = 1;
        foreach (['beginner' => 3, 'intermediate' => 4, 'advanced' => 3] as $level => $weeks) {
            $focus = $levels[$level]['focus'] ?? [];
            for ($i = 0; $i < $weeks; $i++) {
                $skill = $focus[$i % max(1, count($focus))] ?? 'Core practice';
                $schedule[] = [
                    'week' => $week,
                    'level' => (string) ($levels[$level]['title'] ?? $level),
                    'theme' => $skill,
                    'hours' => 6 + ($level === 'advanced' ? 2 : 0),
                    'tasks' => [
                        'Study: ' . $skill,
                        'Practice exercise #' . $week,
                        'Reflect and update learning log',
                    ],
                ];
                $week++;
            }
        }

        return $schedule;
    }

    /**
     * @param  list<string>  $missing
     * @return list<array{order: int, skill: string, why: string}>
     */
    private function prioritySequence(array $missing): array
    {
        $out = [];
        foreach (array_slice($missing, 0, 8) as $i => $skill) {
            $out[] = [
                'order' => $i + 1,
                'skill' => $skill,
                'why' => $i < 2 ? 'Highest leverage for target role readiness' : 'Builds depth after foundations',
            ];
        }

        return $out;
    }

    /**
     * @param  list<string>  $missing
     * @return list<array{title: string, provider: string, skill: string, level: string}>
     */
    private function courses(array $missing): array
    {
        $providers = ['Coursera', 'edX', 'Udemy', 'LinkedIn Learning', 'Google Career Certificates'];
        $out = [];
        foreach (array_slice($missing, 0, 6) as $i => $skill) {
            $out[] = [
                'title' => $skill . ' career track',
                'provider' => $providers[$i % count($providers)],
                'skill' => $skill,
                'level' => $i < 2 ? 'Beginner' : ($i < 4 ? 'Intermediate' : 'Advanced'),
            ];
        }

        return $out;
    }

    /**
     * @param  list<string>  $missing
     * @return list<array{name: string, maps_to: string, timing: string}>
     */
    private function certifications(array $missing, string $goal): array
    {
        $out = [];
        $g = mb_strtolower($goal);
        if (str_contains($g, 'nurs') || str_contains($g, 'health')) {
            $out[] = ['name' => 'BLS / ACLS', 'maps_to' => 'Clinical readiness', 'timing' => 'Weeks 3–5'];
        }
        if (str_contains($g, 'tech') || str_contains($g, 'engineer') || str_contains($g, 'develop')) {
            $out[] = ['name' => 'Cloud foundations certificate', 'maps_to' => 'Technical credibility', 'timing' => 'Weeks 4–8'];
        }
        foreach (array_slice($missing, 0, 3) as $skill) {
            $out[] = [
                'name' => 'Certificate: ' . $skill,
                'maps_to' => $skill,
                'timing' => 'After intermediate phase',
            ];
            if (count($out) >= 5) {
                break;
            }
        }

        return $out;
    }

    /**
     * @param  list<string>  $missing
     * @return list<array{title: string, focus: string}>
     */
    private function books(array $missing, string $goal): array
    {
        $out = [
            ['title' => 'Deep Work', 'focus' => 'Focus and learning discipline'],
            ['title' => 'Atomic Habits', 'focus' => 'Weekly schedule consistency'],
        ];
        foreach (array_slice($missing, 0, 3) as $skill) {
            $out[] = ['title' => 'Practical guide to ' . $skill, 'focus' => $skill];
        }
        $out[] = ['title' => 'Career storytelling for ' . $goal, 'focus' => 'Interview narrative'];

        return array_slice($out, 0, 6);
    }

    /**
     * @param  list<string>  $missing
     * @return list<array{title: string, channel: string, skill: string}>
     */
    private function youtube(array $missing): array
    {
        $channels = ['freeCodeCamp', 'CrashCourse', 'Google Career Certificates', 'TED-Ed', 'Harvard CS50'];
        $out = [];
        foreach (array_slice($missing, 0, 5) as $i => $skill) {
            $out[] = [
                'title' => $skill . ' explained in practice',
                'channel' => $channels[$i % count($channels)],
                'skill' => $skill,
            ];
        }

        return $out;
    }

    /**
     * @param  list<string>  $missing
     * @return list<array{title: string, level: string, deliverable: string}>
     */
    private function projects(array $missing, string $goal): array
    {
        $out = [];
        foreach (array_slice($missing, 0, 4) as $i => $skill) {
            $out[] = [
                'title' => 'Project: apply ' . $skill,
                'level' => $i === 0 ? 'Beginner' : ($i < 3 ? 'Intermediate' : 'Advanced'),
                'deliverable' => 'Documented case + screenshots/notes for portfolio',
            ];
        }
        $out[] = [
            'title' => 'Capstone aligned to ' . $goal,
            'level' => 'Advanced',
            'deliverable' => 'Portfolio-ready case study with outcomes',
        ];

        return $out;
    }

    /**
     * @param  list<string>  $missing
     * @return list<string>
     */
    private function portfolio(array $missing, string $goal): array
    {
        return [
            'Add a portfolio section titled toward: ' . $goal,
            'Publish 1 case study for each intermediate project',
            'Highlight closing of gaps: ' . implode(', ', array_slice($missing, 0, 3)),
            'Include metrics (time saved, quality, impact) on every project card',
            'Link certifications and course completions next to related skills',
        ];
    }

    /**
     * @param  array<string, mixed>  $levels
     * @param  list<array{title: string, level: string, deliverable: string}>  $projects
     * @return list<array{key: string, title: string, phase: string, done: bool}>
     */
    private function milestones(array $levels, array $projects): array
    {
        $milestones = [
            ['key' => 'm_setup', 'title' => 'Set weekly learning cadence', 'phase' => 'Beginner', 'done' => false],
            ['key' => 'm_beginner', 'title' => 'Complete beginner focus skills', 'phase' => 'Beginner', 'done' => false],
            ['key' => 'm_first_project', 'title' => 'Ship first practice project', 'phase' => 'Intermediate', 'done' => false],
            ['key' => 'm_cert', 'title' => 'Earn first recommended certificate', 'phase' => 'Intermediate', 'done' => false],
            ['key' => 'm_portfolio', 'title' => 'Publish portfolio case study', 'phase' => 'Advanced', 'done' => false],
            ['key' => 'm_interview', 'title' => 'Complete mock interview round', 'phase' => 'Advanced', 'done' => false],
        ];
        if ($projects !== []) {
            $milestones[] = [
                'key' => 'm_capstone',
                'title' => 'Finish capstone: ' . (string) ($projects[count($projects) - 1]['title'] ?? 'Capstone'),
                'phase' => 'Advanced',
                'done' => false,
            ];
        }

        return $milestones;
    }

    /** @param array<string, mixed> $context */
    private function alignmentScore(array $context, string $goal): int
    {
        $score = 40;
        if ($goal !== '' && $goal !== 'Career advancement') {
            $score += 15;
        }
        if ((int) ($context['gap_percentage'] ?? 0) > 0) {
            $score += 15;
        }
        if ((float) ($context['salary_target'] ?? 0) > 0) {
            $score += 10;
        }
        if (trim((string) ($context['coach_target_role'] ?? '')) !== '') {
            $score += 10;
        }
        if ((int) ($context['readiness_score'] ?? 0) >= 50) {
            $score += 10;
        }

        return max(0, min(100, $score));
    }

    /** @param array<string, mixed> $context */
    private function alignmentNotes(array $context, string $goal, int $score): string
    {
        $parts = ['Goal: ' . $goal];
        if ((int) ($context['gap_percentage'] ?? 0) > 0) {
            $parts[] = 'Skill gap ' . (int) $context['gap_percentage'] . '% informs priority sequence';
        }
        if ((float) ($context['salary_target'] ?? 0) > 0) {
            $parts[] = 'Salary target ' . (string) ($context['salary_currency'] ?? '') . ' '
                . number_format((float) $context['salary_target'], 0) . ' anchors advanced milestones';
        }
        if (trim((string) ($context['coach_target_role'] ?? '')) !== '') {
            $parts[] = 'Career coach target role: ' . (string) $context['coach_target_role'];
        }
        $parts[] = 'Alignment score ' . $score . '/100';

        return implode('. ', $parts) . '.';
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

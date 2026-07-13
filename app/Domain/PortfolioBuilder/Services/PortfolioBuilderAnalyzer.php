<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\PortfolioBuilder\Services;

use JobVisa\App\Domain\PortfolioBuilder\DTO\PortfolioPlanDTO;
use JobVisa\App\Domain\PortfolioBuilder\Support\PortfolioBuilderVersion;

/**
 * Deterministic portfolio & project plan builder — no external AI APIs.
 */
final class PortfolioBuilderAnalyzer
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function generate(int $resumeId, int $userId, array $context): PortfolioPlanDTO
    {
        $goal = trim((string) ($context['career_goal'] ?? 'Career advancement'));
        if ($goal === '') {
            $goal = 'Career advancement';
        }

        $skills = $this->stringList($context['resume_skills'] ?? []);
        $missing = $this->stringList($context['missing_skills'] ?? []);
        $existingProjects = (int) ($context['existing_project_count'] ?? 0);
        $existingPortfolio = (int) ($context['existing_portfolio_count'] ?? 0);
        $lpProjects = is_array($context['learning_projects'] ?? null) ? $context['learning_projects'] : [];

        $projects = $this->buildProjects($goal, $skills, $missing, $lpProjects);
        $strength = $this->strengthScore($context, count($projects), $existingProjects, $existingPortfolio);
        $recruiter = $this->recruiterEvaluation($strength, $projects, $context);
        $caseStudies = $this->caseStudies($projects);
        $stars = $this->starAchievements($projects, $goal);
        $resumeReady = $this->resumeReadyDescriptions($projects);

        $plan = [
            'headline' => 'Portfolio plan for: ' . $goal,
            'summary' => sprintf(
                'Recommended %d portfolio projects spanning GitHub, full-stack, mobile, UI/UX and data/AI tracks, with case studies and STAR achievements.',
                count($projects)
            ),
            'projects' => $projects,
            'github_ideas' => array_values(array_filter($projects, static fn (array $p): bool => ($p['category'] ?? '') === 'github')),
            'fullstack_ideas' => array_values(array_filter($projects, static fn (array $p): bool => ($p['category'] ?? '') === 'fullstack')),
            'mobile_ideas' => array_values(array_filter($projects, static fn (array $p): bool => ($p['category'] ?? '') === 'mobile')),
            'uiux_ideas' => array_values(array_filter($projects, static fn (array $p): bool => ($p['category'] ?? '') === 'uiux')),
            'datascience_ideas' => array_values(array_filter($projects, static fn (array $p): bool => ($p['category'] ?? '') === 'datascience')),
            'case_studies' => $caseStudies,
            'star_achievements' => $stars,
            'resume_ready_descriptions' => $resumeReady,
            'recruiter_evaluation' => $recruiter,
            'priority_order' => array_map(
                static fn (array $p): array => [
                    'priority' => $p['priority'],
                    'title' => $p['title'],
                    'category' => $p['category'],
                    'difficulty' => $p['difficulty'],
                    'weeks' => $p['estimated_weeks'],
                ],
                $projects
            ),
            'signals' => [
                'existing_projects' => $existingProjects,
                'existing_portfolio' => $existingPortfolio,
                'gap_percentage' => (int) ($context['gap_percentage'] ?? 0),
                'learning_path_weeks' => (int) ($context['learning_path_weeks'] ?? 0),
                'target_job' => (string) ($context['target_job_title'] ?? ''),
            ],
        ];

        return new PortfolioPlanDTO(
            0,
            $resumeId,
            $userId,
            isset($context['job_id']) && (int) $context['job_id'] > 0 ? (int) $context['job_id'] : null,
            $goal,
            $strength,
            count($projects),
            (int) ($recruiter['score'] ?? 0),
            $plan,
            PortfolioBuilderVersion::CURRENT,
            '',
        );
    }

    /**
     * @param  list<string>  $skills
     * @param  list<string>  $missing
     * @param  list<mixed>  $lpProjects
     * @return list<array<string, mixed>>
     */
    private function buildProjects(string $goal, array $skills, array $missing, array $lpProjects): array
    {
        $focus = $missing !== [] ? $missing : ($skills !== [] ? $skills : ['Professional delivery', 'Documentation', 'Problem solving']);
        $primary = $focus[0] ?? 'Core skills';
        $secondary = $focus[1] ?? 'Collaboration';
        $g = mb_strtolower($goal);

        $projects = [
            [
                'key' => 'gh_readme',
                'category' => 'github',
                'title' => 'GitHub profile + pinned showcase for ' . $goal,
                'summary' => 'Polish README, pinned repos, and contribution narrative tied to ' . $primary,
                'skills_demonstrated' => array_values(array_unique(array_merge([$primary, 'Documentation', 'Git'], array_slice($skills, 0, 2)))),
                'difficulty' => 'Beginner',
                'estimated_weeks' => 1,
                'priority' => 1,
                'github_repo_idea' => 'portfolio-' . $this->slug($goal) . '-profile',
            ],
            [
                'key' => 'fs_app',
                'category' => 'fullstack',
                'title' => 'Full-stack case app: ' . $primary . ' tracker',
                'summary' => 'End-to-end web app demonstrating CRUD, auth, and measurable outcomes for ' . $goal,
                'skills_demonstrated' => array_values(array_unique(array_merge([$primary, $secondary, 'APIs', 'Databases'], array_slice($skills, 0, 2)))),
                'difficulty' => 'Intermediate',
                'estimated_weeks' => 4,
                'priority' => 2,
                'github_repo_idea' => $this->slug($primary) . '-fullstack-tracker',
            ],
            [
                'key' => 'mobile_app',
                'category' => 'mobile',
                'title' => 'Mobile companion for ' . $secondary,
                'summary' => 'Mobile UI flow with offline notes and role-aligned workflows',
                'skills_demonstrated' => [$secondary, 'Mobile UX', 'Prototyping', 'User research'],
                'difficulty' => 'Intermediate',
                'estimated_weeks' => 3,
                'priority' => 3,
                'github_repo_idea' => $this->slug($secondary) . '-mobile-companion',
            ],
            [
                'key' => 'uiux_kit',
                'category' => 'uiux',
                'title' => 'UI/UX redesign case study for target role',
                'summary' => 'Before/after flows, accessibility notes, and recruiter-facing Figma/storyboard summary',
                'skills_demonstrated' => ['UI design', 'UX research', 'Accessibility', $primary],
                'difficulty' => 'Beginner',
                'estimated_weeks' => 2,
                'priority' => 4,
                'github_repo_idea' => $this->slug($goal) . '-ux-case-study',
            ],
            [
                'key' => 'ds_insight',
                'category' => 'datascience',
                'title' => (str_contains($g, 'nurs') || str_contains($g, 'health')
                    ? 'Healthcare insight dashboard'
                    : 'Data/AI insight mini-project') . ' on ' . $primary,
                'summary' => 'Analyze a public dataset, visualize insights, and document decision impact for employers',
                'skills_demonstrated' => ['Data analysis', 'Visualization', 'Storytelling', $primary],
                'difficulty' => 'Advanced',
                'estimated_weeks' => 3,
                'priority' => 5,
                'github_repo_idea' => $this->slug($primary) . '-insight-lab',
            ],
        ];

        $priority = 6;
        foreach (array_slice($lpProjects, 0, 2) as $i => $lp) {
            if (!is_array($lp)) {
                continue;
            }
            $title = trim((string) ($lp['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $projects[] = [
                'key' => 'lp_' . ($i + 1),
                'category' => 'fullstack',
                'title' => 'Learning-path build: ' . $title,
                'summary' => (string) ($lp['deliverable'] ?? 'Portfolio-ready deliverable from your learning path'),
                'skills_demonstrated' => array_values(array_unique(array_merge([$primary], array_slice($focus, 0, 3)))),
                'difficulty' => (string) ($lp['level'] ?? 'Intermediate'),
                'estimated_weeks' => 2,
                'priority' => $priority++,
                'github_repo_idea' => $this->slug($title),
            ];
        }

        usort($projects, static fn (array $a, array $b): int => ((int) $a['priority']) <=> ((int) $b['priority']));

        return $projects;
    }

    /**
     * @param  list<array<string, mixed>>  $projects
     * @param  array<string, mixed>  $context
     */
    private function strengthScore(array $context, int $projectCount, int $existingProjects, int $existingPortfolio): int
    {
        $score = 30;
        $score += min(25, $projectCount * 4);
        $score += min(15, $existingProjects * 5);
        $score += min(10, $existingPortfolio * 5);
        if ((int) ($context['resume_overall'] ?? 0) >= 60) {
            $score += 10;
        }
        if ((int) ($context['gap_percentage'] ?? 100) <= 40) {
            $score += 10;
        }
        if (trim((string) ($context['career_goal'] ?? '')) !== '') {
            $score += 5;
        }

        return max(0, min(100, $score));
    }

    /**
     * @param  list<array<string, mixed>>  $projects
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function recruiterEvaluation(int $strength, array $projects, array $context): array
    {
        $score = (int) round(($strength * 0.7) + (min(100, count($projects) * 12) * 0.3));
        $pros = [];
        $cons = [];
        if (count($projects) >= 5) {
            $pros[] = 'Breadth across GitHub, product, mobile, UX and data tracks';
        }
        if ((int) ($context['existing_portfolio_count'] ?? 0) > 0) {
            $pros[] = 'Existing portfolio assets can accelerate publishing';
        } else {
            $cons[] = 'No live portfolio items yet — publish at least two case studies first';
        }
        if ((int) ($context['gap_percentage'] ?? 0) >= 50) {
            $cons[] = 'Skill gaps remain high; prioritize P1–P2 projects before advanced builds';
        }
        if ($pros === []) {
            $pros[] = 'Clear priority order helps recruiters scan impact quickly';
        }
        if ($cons === []) {
            $cons[] = 'Add metrics (users, time saved, accuracy) to each case study';
        }

        return [
            'score' => max(0, min(100, $score)),
            'label' => match (true) {
                $score >= 80 => 'Recruiter-ready',
                $score >= 60 => 'Competitive with polish',
                $score >= 40 => 'Needs stronger proof',
                default => 'Early-stage portfolio',
            },
            'pros' => $pros,
            'cons' => $cons,
            'advice' => 'Lead with priority 1–3 projects, quantify outcomes, and mirror language from your target job.',
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $projects
     * @return list<array<string, mixed>>
     */
    private function caseStudies(array $projects): array
    {
        $out = [];
        foreach (array_slice($projects, 0, 4) as $p) {
            $out[] = [
                'project' => (string) ($p['title'] ?? ''),
                'problem' => 'Hiring managers need proof of ' . implode(', ', array_slice($p['skills_demonstrated'] ?? [], 0, 2)),
                'approach' => 'Scoped MVP in ' . (int) ($p['estimated_weeks'] ?? 2) . ' weeks with weekly demos and documented decisions',
                'result' => 'Portfolio artifact with screenshots, README, and measurable before/after notes',
                'recruiter_hook' => 'Shows ' . (string) ($p['difficulty'] ?? 'Intermediate') . ' execution aligned to role needs',
            ];
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $projects
     * @return list<array{project: string, situation: string, task: string, action: string, result: string}>
     */
    private function starAchievements(array $projects, string $goal): array
    {
        $out = [];
        foreach (array_slice($projects, 0, 4) as $p) {
            $title = (string) ($p['title'] ?? 'Portfolio project');
            $out[] = [
                'project' => $title,
                'situation' => 'Targeting roles related to ' . $goal . ' with limited public proof of ' . ($p['skills_demonstrated'][0] ?? 'core skills'),
                'task' => 'Deliver a ' . (string) ($p['difficulty'] ?? 'Intermediate') . ' portfolio artifact in '
                    . (int) ($p['estimated_weeks'] ?? 2) . ' weeks',
                'action' => 'Built ' . $title . ' using ' . implode(', ', array_slice($p['skills_demonstrated'] ?? [], 0, 3))
                    . '; documented decisions and published to GitHub',
                'result' => 'Created a recruiter-ready case study demonstrating measurable practice and role alignment',
            ];
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $projects
     * @return list<array{title: string, bullets: list<string>}>
     */
    private function resumeReadyDescriptions(array $projects): array
    {
        $out = [];
        foreach ($projects as $p) {
            $skills = array_slice($p['skills_demonstrated'] ?? [], 0, 4);
            $out[] = [
                'title' => (string) ($p['title'] ?? ''),
                'bullets' => [
                    'Built a ' . strtolower((string) ($p['difficulty'] ?? 'intermediate')) . ' '
                        . (string) ($p['category'] ?? 'portfolio') . ' project demonstrating '
                        . implode(', ', $skills),
                    'Documented architecture, trade-offs, and outcomes for recruiter review in '
                        . (int) ($p['estimated_weeks'] ?? 2) . ' weeks',
                    'Published reusable assets (README, screenshots, repo: '
                        . (string) ($p['github_repo_idea'] ?? 'portfolio-project') . ')',
                ],
            ];
        }

        return $out;
    }

    private function slug(string $value): string
    {
        $value = mb_strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/u', '-', $value) ?? 'project';
        $value = trim($value, '-');

        return $value !== '' ? mb_substr($value, 0, 48) : 'project';
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

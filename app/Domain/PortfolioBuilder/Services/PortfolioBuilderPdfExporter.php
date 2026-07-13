<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\PortfolioBuilder\Services;

use JobVisa\App\Domain\CoverLetter\Services\CoverLetterPdfExporter;
use JobVisa\App\Domain\PortfolioBuilder\DTO\PortfolioPlanDTO;

final class PortfolioBuilderPdfExporter
{
    public function __construct(
        private readonly CoverLetterPdfExporter $pdf
    ) {
    }

    public function export(PortfolioPlanDTO $dto): string
    {
        $p = $dto->plan;
        $lines = [];
        $lines[] = 'AI Portfolio & Project Builder';
        $lines[] = 'Goal: ' . $dto->careerGoal;
        $lines[] = 'Strength: ' . $dto->strengthScore . '/100';
        $lines[] = 'Recruiter score: ' . $dto->recruiterScore . '/100';
        $lines[] = 'Projects: ' . $dto->projectCount;
        $lines[] = '';
        $lines[] = (string) ($p['summary'] ?? '');
        $eval = $p['recruiter_evaluation'] ?? [];
        if (is_array($eval)) {
            $lines[] = '';
            $lines[] = 'Recruiter evaluation: ' . (string) ($eval['label'] ?? '') . ' (' . (string) ($eval['score'] ?? '') . ')';
            $lines[] = (string) ($eval['advice'] ?? '');
        }
        $lines[] = '';
        $lines[] = 'Priority projects:';
        foreach (($p['projects'] ?? []) as $project) {
            if (!is_array($project)) {
                continue;
            }
            $lines[] = '- [P' . (string) ($project['priority'] ?? '') . '] '
                . (string) ($project['title'] ?? '')
                . ' (' . (string) ($project['category'] ?? '') . ', '
                . (string) ($project['difficulty'] ?? '') . ', '
                . (string) ($project['estimated_weeks'] ?? '') . 'w)';
            $lines[] = '  Skills: ' . implode(', ', $project['skills_demonstrated'] ?? []);
            $lines[] = '  Repo idea: ' . (string) ($project['github_repo_idea'] ?? '');
        }
        $lines[] = '';
        $lines[] = 'Case studies:';
        foreach (($p['case_studies'] ?? []) as $c) {
            if (is_array($c)) {
                $lines[] = '- ' . (string) ($c['project'] ?? '') . ': ' . (string) ($c['result'] ?? '');
            }
        }
        $lines[] = '';
        $lines[] = 'STAR achievements:';
        foreach (($p['star_achievements'] ?? []) as $s) {
            if (is_array($s)) {
                $lines[] = '- ' . (string) ($s['project'] ?? '');
                $lines[] = '  S: ' . (string) ($s['situation'] ?? '');
                $lines[] = '  T: ' . (string) ($s['task'] ?? '');
                $lines[] = '  A: ' . (string) ($s['action'] ?? '');
                $lines[] = '  R: ' . (string) ($s['result'] ?? '');
            }
        }
        $lines[] = '';
        $lines[] = 'Resume-ready descriptions:';
        foreach (($p['resume_ready_descriptions'] ?? []) as $r) {
            if (!is_array($r)) {
                continue;
            }
            $lines[] = '- ' . (string) ($r['title'] ?? '');
            foreach (($r['bullets'] ?? []) as $b) {
                $lines[] = '  • ' . (string) $b;
            }
        }

        return $this->pdf->export('Portfolio Builder', implode("\n", $lines));
    }
}

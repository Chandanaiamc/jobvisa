<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\LearningPath\Services;

use JobVisa\App\Domain\CoverLetter\Services\CoverLetterPdfExporter;
use JobVisa\App\Domain\LearningPath\DTO\LearningPathDTO;

final class LearningPathPdfExporter
{
    public function __construct(
        private readonly CoverLetterPdfExporter $pdf
    ) {
    }

    public function export(LearningPathDTO $dto): string
    {
        $p = $dto->path;
        $lines = [];
        $lines[] = 'Personalized Learning Path';
        $lines[] = 'Goal: ' . $dto->careerGoal;
        $lines[] = 'Timeline: ' . $dto->timelineWeeks . ' weeks';
        $lines[] = 'Progress: ' . $dto->progressPercent . '% (' . $dto->milestonesDone . '/' . $dto->milestonesTotal . ' milestones)';
        $lines[] = 'Career alignment: ' . $dto->alignmentScore . '/100';
        $lines[] = '';
        $lines[] = (string) ($p['summary'] ?? '');
        $lines[] = '';
        $lines[] = 'Levels:';
        foreach (['beginner', 'intermediate', 'advanced'] as $key) {
            $level = $p['levels'][$key] ?? null;
            if (!is_array($level)) {
                continue;
            }
            $lines[] = '- ' . (string) ($level['title'] ?? $key) . ': ' . implode(', ', $level['focus'] ?? []);
        }
        $lines[] = '';
        $lines[] = 'Priority sequence:';
        foreach (($p['priority_sequence'] ?? []) as $row) {
            if (is_array($row)) {
                $lines[] = '- #' . (string) ($row['order'] ?? '') . ' ' . (string) ($row['skill'] ?? '');
            }
        }
        $lines[] = '';
        $lines[] = 'Weekly schedule (first 6 weeks):';
        foreach (array_slice($p['weekly_schedule'] ?? [], 0, 6) as $w) {
            if (is_array($w)) {
                $lines[] = '- Week ' . (string) ($w['week'] ?? '') . ': ' . (string) ($w['theme'] ?? '')
                    . ' (' . (string) ($w['hours'] ?? '') . 'h)';
            }
        }
        $lines[] = '';
        $lines[] = 'Courses:';
        foreach (($p['courses'] ?? []) as $c) {
            if (is_array($c)) {
                $lines[] = '- ' . (string) ($c['title'] ?? '') . ' [' . (string) ($c['provider'] ?? '') . ']';
            }
        }
        $lines[] = '';
        $lines[] = 'Certifications:';
        foreach (($p['certifications'] ?? []) as $c) {
            if (is_array($c)) {
                $lines[] = '- ' . (string) ($c['name'] ?? '');
            }
        }
        $lines[] = '';
        $lines[] = 'Books:';
        foreach (($p['books'] ?? []) as $b) {
            if (is_array($b)) {
                $lines[] = '- ' . (string) ($b['title'] ?? '');
            }
        }
        $lines[] = '';
        $lines[] = 'YouTube:';
        foreach (($p['youtube'] ?? []) as $y) {
            if (is_array($y)) {
                $lines[] = '- ' . (string) ($y['title'] ?? '') . ' (' . (string) ($y['channel'] ?? '') . ')';
            }
        }
        $lines[] = '';
        $lines[] = 'Practice projects:';
        foreach (($p['practice_projects'] ?? []) as $pr) {
            if (is_array($pr)) {
                $lines[] = '- ' . (string) ($pr['title'] ?? '');
            }
        }
        $lines[] = '';
        $lines[] = 'Portfolio recommendations:';
        foreach (($p['portfolio'] ?? []) as $item) {
            $lines[] = '- ' . (string) $item;
        }
        $lines[] = '';
        $lines[] = 'Milestones:';
        foreach (($p['milestones'] ?? []) as $m) {
            if (is_array($m)) {
                $mark = !empty($m['done']) ? '[x]' : '[ ]';
                $lines[] = '- ' . $mark . ' ' . (string) ($m['title'] ?? '');
            }
        }

        return $this->pdf->export('Learning Path', implode("\n", $lines));
    }
}

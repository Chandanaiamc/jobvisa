<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\SkillGap\Services;

use JobVisa\App\Domain\CoverLetter\Services\CoverLetterPdfExporter;
use JobVisa\App\Domain\SkillGap\DTO\SkillGapAnalysisDTO;

final class SkillGapPdfExporter
{
    public function __construct(
        private readonly CoverLetterPdfExporter $pdf
    ) {
    }

    public function export(SkillGapAnalysisDTO $dto): string
    {
        $a = $dto->analysis;
        $cmp = $a['comparison'] ?? [];
        $lines = [];
        $lines[] = 'Skill Gap Analysis';
        $lines[] = 'Target job: ' . $dto->jobTitle;
        $lines[] = 'Gap: ' . $dto->gapPercentage . '% (' . (string) ($a['gap_label'] ?? '') . ')';
        $lines[] = 'Career readiness: ' . $dto->readinessScore . '/100 (' . (string) ($a['readiness_label'] ?? '') . ')';
        $lines[] = 'Skill match score: ' . $dto->matchSkillsScore . '/100';
        $lines[] = '';
        $lines[] = (string) ($a['explanation'] ?? '');
        $lines[] = '';
        $lines[] = 'Matching skills: ' . implode(', ', $cmp['matched_skills'] ?? []);
        $lines[] = 'Missing skills: ' . implode(', ', $cmp['missing_skills'] ?? []);
        $lines[] = '';
        $lines[] = 'Strengths:';
        foreach (($a['strengths'] ?? []) as $s) {
            $lines[] = '- ' . (string) $s;
        }
        $lines[] = '';
        $lines[] = 'Weaknesses:';
        foreach (($a['weaknesses'] ?? []) as $s) {
            $lines[] = '- ' . (string) $s;
        }
        $lines[] = '';
        $lines[] = 'Priority learning order:';
        foreach (($a['priority_learning_order'] ?? []) as $row) {
            if (is_array($row)) {
                $lines[] = '- [' . (string) ($row['priority'] ?? '') . '] ' . (string) ($row['skill'] ?? '');
            }
        }
        $lines[] = '';
        $lines[] = 'Learning roadmap:';
        foreach (($a['learning_roadmap'] ?? []) as $phase) {
            if (is_array($phase)) {
                $lines[] = '- ' . (string) ($phase['phase'] ?? '') . ' (' . (string) ($phase['weeks'] ?? '') . 'w): '
                    . (string) ($phase['focus'] ?? '');
            }
        }
        $lines[] = '';
        $lines[] = 'Recommended certifications:';
        foreach (($a['recommended_certifications'] ?? []) as $c) {
            if (is_array($c)) {
                $lines[] = '- ' . (string) ($c['name'] ?? '') . ' → ' . (string) ($c['maps_to'] ?? '');
            }
        }
        $lines[] = '';
        $lines[] = 'Recommended courses:';
        foreach (($a['recommended_courses'] ?? []) as $c) {
            if (is_array($c)) {
                $lines[] = '- ' . (string) ($c['title'] ?? '') . ' (' . (string) ($c['provider'] ?? '') . ')';
            }
        }

        return $this->pdf->export('Skill Gap Analysis', implode("\n", $lines));
    }
}

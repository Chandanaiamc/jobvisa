<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\ApplicationAssistant\Services;

use JobVisa\App\Domain\ApplicationAssistant\DTO\ApplicationAnalysisDTO;
use JobVisa\App\Domain\CoverLetter\Services\CoverLetterPdfExporter;

/**
 * PDF export for application readiness analysis.
 */
final class ApplicationAssistantPdfExporter
{
    public function __construct(
        private readonly CoverLetterPdfExporter $pdf
    ) {
    }

    public function export(ApplicationAnalysisDTO $dto): string
    {
        $a = $dto->analysis;
        $lines = [];
        $lines[] = 'Application Assistant Analysis';
        $lines[] = $dto->jobTitle . ' vs ' . $dto->resumeTitle;
        $lines[] = 'Readiness: ' . $dto->readinessScore . '/100 (' . (string) ($a['readiness_label'] ?? '') . ')';
        $lines[] = 'Match ' . $dto->matchOverall . ' | Resume ' . $dto->resumeOverall
            . ' | Skills ' . $dto->skillsScore . ' | Experience ' . $dto->experienceScore
            . ' | Education ' . $dto->educationScore . ' | Certs ' . $dto->certificationScore
            . ' | Portfolio ' . $dto->portfolioScore;
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
        $lines[] = 'Recommendations:';
        foreach (($a['recommendations'] ?? []) as $r) {
            if (is_array($r)) {
                $lines[] = '- [' . (string) ($r['priority'] ?? '') . '] ' . (string) ($r['action'] ?? '');
            }
        }
        $cmp = $a['comparison'] ?? [];
        $lines[] = '';
        $lines[] = 'Missing skills: ' . implode(', ', $cmp['missing_skills'] ?? []);
        $lines[] = 'Missing ATS keywords: ' . implode(', ', $cmp['missing_ats_keywords'] ?? []);

        return $this->pdf->export('Application Assistant', implode("\n", $lines));
    }
}

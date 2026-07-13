<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\MockInterview\Services;

use JobVisa\App\Domain\CoverLetter\Services\CoverLetterPdfExporter;
use JobVisa\App\Domain\MockInterview\DTO\MockInterviewSessionDTO;

final class MockInterviewPdfExporter
{
    public function __construct(
        private readonly CoverLetterPdfExporter $pdf
    ) {
    }

    public function export(MockInterviewSessionDTO $dto): string
    {
        $s = $dto->session;
        $report = is_array($s['report'] ?? null) ? $s['report'] : [];
        $analysis = is_array($s['analysis'] ?? null) ? $s['analysis'] : [];
        $lines = [];
        $lines[] = 'Mock Interview Report';
        $lines[] = 'Role: ' . $dto->jobTitle . ' · Level: ' . $dto->careerLevel;
        $lines[] = 'Status: ' . $dto->status;
        $lines[] = 'Overall: ' . $dto->overallScore . '/100';
        $lines[] = 'Communication: ' . $dto->communicationScore
            . ' | Technical: ' . $dto->technicalScore
            . ' | Confidence: ' . $dto->confidenceScore
            . ' | STAR: ' . $dto->starScore;
        $lines[] = '';
        $lines[] = (string) ($report['summary'] ?? $analysis['summary'] ?? '');
        $lines[] = 'Readiness: ' . (string) ($analysis['readiness_label'] ?? '');
        $lines[] = '';
        $lines[] = 'Questions:';
        foreach (($s['questions'] ?? []) as $q) {
            if (!is_array($q)) {
                continue;
            }
            $id = (string) ($q['id'] ?? '');
            $lines[] = '- [' . strtoupper((string) ($q['type'] ?? '')) . '] ' . (string) ($q['prompt'] ?? '');
            $ans = (string) (($s['answers'][$id] ?? ''));
            if ($ans !== '') {
                $lines[] = '  Answer: ' . mb_substr($ans, 0, 280);
            }
        }
        $lines[] = '';
        $lines[] = 'Improvements:';
        foreach (($analysis['improvements'] ?? $report['top_improvements'] ?? []) as $tip) {
            $lines[] = '- ' . (string) $tip;
        }
        $lines[] = '';
        $lines[] = 'Follow-up questions:';
        foreach (($analysis['follow_up_questions'] ?? $report['recommended_follow_ups'] ?? []) as $fq) {
            $lines[] = '- ' . (string) $fq;
        }
        $lines[] = '';
        $lines[] = 'Context notes:';
        foreach (($report['context_notes'] ?? []) as $n) {
            $lines[] = '- ' . (string) $n;
        }

        return $this->pdf->export('Mock Interview', implode("\n", $lines));
    }
}

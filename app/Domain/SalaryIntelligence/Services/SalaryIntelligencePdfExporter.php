<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\SalaryIntelligence\Services;

use JobVisa\App\Domain\CoverLetter\Services\CoverLetterPdfExporter;
use JobVisa\App\Domain\SalaryIntelligence\DTO\SalaryPredictionDTO;

final class SalaryIntelligencePdfExporter
{
    public function __construct(
        private readonly CoverLetterPdfExporter $pdf
    ) {
    }

    public function export(SalaryPredictionDTO $dto): string
    {
        $a = $dto->analysis;
        $lines = [];
        $lines[] = 'Salary Intelligence Report';
        $lines[] = $dto->jobTitle . ' · ' . $dto->careerLevel . ' · ' . $dto->locationLabel;
        $lines[] = 'Industry: ' . $dto->industry;
        $lines[] = '';
        $lines[] = 'Predicted: ' . $dto->currency . ' ' . number_format($dto->predictedSalary, 0);
        $lines[] = 'Range: ' . $dto->currency . ' ' . number_format($dto->minSalary, 0)
            . ' – ' . number_format($dto->maxSalary, 0);
        $lines[] = 'Market average: ' . $dto->currency . ' ' . number_format($dto->marketAverage, 0);
        $lines[] = 'Recommended target: ' . $dto->currency . ' ' . number_format($dto->recommendedTarget, 0);
        $lines[] = 'Confidence: ' . $dto->confidenceScore . '/100';
        $lines[] = '';
        $lines[] = 'Explanation:';
        $lines[] = (string) ($a['explanation'] ?? '');
        $lines[] = '';
        $lines[] = 'Impacts:';
        foreach (($a['impacts'] ?? []) as $impact) {
            if (is_array($impact)) {
                $lines[] = '- ' . (string) ($impact['label'] ?? '') . ': '
                    . (string) ($impact['pct'] ?? 0) . '% (' . (string) ($impact['detail'] ?? '') . ')';
            }
        }
        $lines[] = '';
        $lines[] = 'Negotiation tips:';
        foreach (($a['negotiation_tips'] ?? []) as $tip) {
            $lines[] = '- ' . (string) $tip;
        }

        return $this->pdf->export('Salary Intelligence', implode("\n", $lines));
    }
}

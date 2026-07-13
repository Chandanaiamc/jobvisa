<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\OfferEvaluation\Services;

use JobVisa\App\Domain\CoverLetter\Services\CoverLetterPdfExporter;
use JobVisa\App\Domain\OfferEvaluation\DTO\OfferEvaluationAnalysisDTO;

final class OfferEvaluationPdfExporter
{
    public function __construct(
        private readonly CoverLetterPdfExporter $pdf
    ) {
    }

    public function export(OfferEvaluationAnalysisDTO $dto): string
    {
        $a = $dto->analysis;
        $lines = [];
        $lines[] = 'AI Offer Evaluation Report';
        $lines[] = 'Role: ' . $dto->jobTitle . ($dto->companyName !== '' ? ' @ ' . $dto->companyName : '');
        $lines[] = 'Base: ' . number_format($dto->baseSalary, 2) . ' ' . $dto->currency;
        $lines[] = 'Overall: ' . $dto->overallScore . '/100 · Recommendation: ' . strtoupper($dto->recommendation);
        $lines[] = sprintf(
            'Compensation %d | Benefits %d | Growth %d | Lifestyle %d',
            $dto->compensationScore,
            $dto->benefitsScore,
            $dto->growthScore,
            $dto->lifestyleScore
        );
        $lines[] = '';
        $lines[] = (string) ($a['summary'] ?? '');
        $lines[] = '';
        $lines[] = 'Pros:';
        foreach (($a['pros'] ?? []) as $p) {
            $lines[] = '- ' . (string) $p;
        }
        $lines[] = '';
        $lines[] = 'Cons:';
        foreach (($a['cons'] ?? []) as $c) {
            $lines[] = '- ' . (string) $c;
        }
        $lines[] = '';
        $counter = is_array($a['counter_offer'] ?? null) ? $a['counter_offer'] : [];
        $lines[] = 'Counter-offer guidance:';
        $lines[] = '- Ask base: ' . (string) ($counter['ask_base'] ?? '') . ' ' . (string) ($counter['currency'] ?? $dto->currency);
        $lines[] = '- Stretch: ' . (string) ($counter['stretch_base'] ?? '');
        $lines[] = '- Walk-away: ' . (string) ($counter['walk_away_floor'] ?? '');
        $lines[] = '';
        $lines[] = 'Negotiation talking points:';
        foreach (($a['negotiation_talking_points'] ?? []) as $t) {
            $lines[] = '- ' . (string) $t;
        }
        $lines[] = '';
        $lines[] = 'Decision checklist:';
        foreach (($a['decision_checklist'] ?? []) as $item) {
            $lines[] = '- ' . (string) $item;
        }

        return $this->pdf->export('Offer Evaluation', implode("\n", $lines));
    }
}

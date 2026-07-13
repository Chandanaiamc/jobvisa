<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\JobSearchCopilot\Services;

use JobVisa\App\Domain\CoverLetter\Services\CoverLetterPdfExporter;
use JobVisa\App\Domain\JobSearchCopilot\DTO\JobSearchCopilotPlanDTO;

final class JobSearchCopilotPdfExporter
{
    public function __construct(
        private readonly CoverLetterPdfExporter $pdf
    ) {
    }

    public function export(JobSearchCopilotPlanDTO $dto): string
    {
        $p = $dto->plan;
        $lines = [];
        $lines[] = 'AI Job Search Copilot Report';
        $lines[] = 'Goal: ' . $dto->careerGoal;
        $lines[] = 'Copilot score: ' . $dto->copilotScore . '/100';
        $lines[] = 'Recommendations: ' . $dto->recommendationCount;
        $lines[] = '';
        $lines[] = (string) ($p['summary'] ?? '');
        $lines[] = '';
        $lines[] = 'Search queries:';
        foreach (($p['search_queries'] ?? []) as $q) {
            $lines[] = '- ' . (string) $q;
        }
        $lines[] = '';
        $lines[] = 'Recommended filters:';
        $filters = is_array($p['recommended_filters'] ?? null) ? $p['recommended_filters'] : [];
        foreach ($filters as $key => $val) {
            if (is_array($val)) {
                $lines[] = '- ' . $key . ': ' . implode(', ', array_map('strval', $val));
            } else {
                $lines[] = '- ' . $key . ': ' . (string) $val;
            }
        }
        $lines[] = '';
        $lines[] = 'Ranked recommendations:';
        foreach (($p['recommendations'] ?? []) as $r) {
            if (!is_array($r)) {
                continue;
            }
            $lines[] = sprintf(
                '- [#%d · %s · %d/100] %s',
                (int) ($r['priority'] ?? 0),
                (string) ($r['category'] ?? ''),
                (int) ($r['score'] ?? 0),
                (string) ($r['title'] ?? '')
            );
            foreach (($r['reasons'] ?? []) as $reason) {
                $lines[] = '  · ' . (string) $reason;
            }
        }
        $lines[] = '';
        $lines[] = 'Weekly plan:';
        foreach (($p['weekly_search_plan'] ?? []) as $day) {
            if (!is_array($day)) {
                continue;
            }
            $lines[] = '- ' . (string) ($day['day'] ?? '') . ': ' . (string) ($day['focus'] ?? '');
        }
        $lines[] = '';
        $lines[] = 'Strategy tips:';
        foreach (($p['strategy_tips'] ?? []) as $tip) {
            $lines[] = '- ' . (string) $tip;
        }
        $lines[] = '';
        $lines[] = 'Alert keywords: ' . implode(', ', array_map('strval', $p['alert_keywords'] ?? []));

        return $this->pdf->export('Job Search Copilot', implode("\n", $lines));
    }
}

<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\OfferEvaluation\Validators;

use JobVisa\App\Domain\OfferEvaluation\Exceptions\OfferEvaluationException;

final class OfferEvaluationValidator
{
    public function assertResumeId(int $resumeId): void
    {
        if ($resumeId < 1) {
            throw OfferEvaluationException::invalidResume();
        }
    }

    public function assertAnalysisId(int $analysisId): void
    {
        if ($analysisId < 1) {
            throw OfferEvaluationException::analysisNotFound();
        }
    }

    public function assertHistoryId(int $historyId): void
    {
        if ($historyId < 1) {
            throw OfferEvaluationException::historyNotFound();
        }
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function normalizeOfferInput(array $input): array
    {
        $title = trim((string) ($input['job_title'] ?? ''));
        $base = (float) ($input['base_salary'] ?? 0);
        if ($title === '' || $base <= 0) {
            throw OfferEvaluationException::invalidOffer();
        }

        $currency = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', (string) ($input['currency'] ?? 'USD')) ?: 'USD', 0, 3));
        if ($currency === '') {
            $currency = 'USD';
        }

        $benefits = $input['benefits'] ?? [];
        if (is_string($benefits)) {
            $benefits = preg_split('/[,;\n]+/', $benefits) ?: [];
        }
        if (!is_array($benefits)) {
            $benefits = [];
        }
        $benefitList = [];
        foreach ($benefits as $b) {
            $s = trim((string) $b);
            if ($s !== '') {
                $benefitList[] = mb_substr($s, 0, 64);
            }
        }

        $jobId = isset($input['job_id']) ? (int) $input['job_id'] : 0;

        return [
            'job_id' => $jobId > 0 ? $jobId : null,
            'company_name' => mb_substr(trim((string) ($input['company_name'] ?? '')), 0, 191),
            'job_title' => mb_substr($title, 0, 191),
            'currency' => $currency,
            'base_salary' => round($base, 2),
            'bonus' => max(0, round((float) ($input['bonus'] ?? 0), 2)),
            'equity_value' => max(0, round((float) ($input['equity_value'] ?? 0), 2)),
            'location' => mb_substr(trim((string) ($input['location'] ?? '')), 0, 128),
            'work_mode' => $this->normalizeWorkMode((string) ($input['work_mode'] ?? 'onsite')),
            'benefits' => array_values(array_unique($benefitList)),
            'notes' => mb_substr(trim((string) ($input['notes'] ?? '')), 0, 2000),
            'contract_months' => max(0, min(60, (int) ($input['contract_months'] ?? 0))),
        ];
    }

    private function normalizeWorkMode(string $mode): string
    {
        $mode = strtolower(trim($mode));

        return match ($mode) {
            'remote', 'hybrid', 'onsite' => $mode,
            default => 'onsite',
        };
    }
}

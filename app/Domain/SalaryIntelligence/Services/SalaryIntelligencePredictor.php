<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\SalaryIntelligence\Services;

use JobVisa\App\Domain\SalaryIntelligence\DTO\SalaryPredictionDTO;
use JobVisa\App\Domain\SalaryIntelligence\Support\SalaryIntelligenceVersion;

/**
 * Deterministic salary range heuristics — no external AI APIs.
 */
final class SalaryIntelligencePredictor
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function predict(int $resumeId, int $userId, array $context): SalaryPredictionDTO
    {
        $years = (float) ($context['years'] ?? 0);
        $skillCount = (int) ($context['skill_count'] ?? 0);
        $certCount = (int) ($context['cert_count'] ?? 0);
        $eduRank = (int) ($context['education_rank'] ?? 0);
        $title = trim((string) ($context['job_title'] ?? 'Professional'));
        $industry = trim((string) ($context['industry'] ?? 'General'));
        $location = trim((string) ($context['location_label'] ?? 'Global'));
        $currency = strtoupper(substr((string) ($context['currency'] ?? 'USD'), 0, 3));
        if ($currency === '') {
            $currency = 'USD';
        }

        $careerLevel = $this->careerLevel($years, $title);
        $base = $this->baseByLevel($careerLevel, $currency);

        $skillImpact = $this->skillImpact($skillCount, $context['premium_skills'] ?? []);
        $experienceImpact = $this->experienceImpact($years);
        $educationImpact = $this->educationImpact($eduRank);
        $certImpact = $this->certificationImpact($certCount);
        $locationImpact = $this->locationImpact($location, $currency);
        $industryImpact = $this->industryImpact($industry);

        $adjusted = $base
            * (1 + $skillImpact)
            * (1 + $experienceImpact)
            * (1 + $educationImpact)
            * (1 + $certImpact)
            * (1 + $locationImpact)
            * (1 + $industryImpact);

        $expected = (float) ($context['expected_salary'] ?? 0);
        $current = (float) ($context['current_salary'] ?? 0);
        $marketFromJobs = (float) ($context['market_from_jobs'] ?? 0);

        if ($expected > 0) {
            $adjusted = ($adjusted * 0.65) + ($expected * 0.35);
        } elseif ($current > 0) {
            $adjusted = ($adjusted * 0.75) + ($current * 1.08 * 0.25);
        }

        if ($marketFromJobs > 0) {
            $adjusted = ($adjusted * 0.70) + ($marketFromJobs * 0.30);
        }

        $predicted = $this->roundMoney($adjusted);
        $spread = 0.14 + max(0, (70 - $this->rawConfidence($context)) / 500);
        $min = $this->roundMoney($predicted * (1 - $spread));
        $max = $this->roundMoney($predicted * (1 + $spread));
        $market = $marketFromJobs > 0
            ? $this->roundMoney($marketFromJobs)
            : $this->roundMoney(($min + $max) / 2);
        $recommended = $this->roundMoney($predicted * 1.06);
        $confidence = $this->confidence($context, $marketFromJobs > 0);

        $impacts = [
            'skill' => ['label' => 'Skill impact', 'pct' => round($skillImpact * 100, 1), 'detail' => $skillCount . ' skills on resume'],
            'experience' => ['label' => 'Experience impact', 'pct' => round($experienceImpact * 100, 1), 'detail' => $years . ' years'],
            'education' => ['label' => 'Education impact', 'pct' => round($educationImpact * 100, 1), 'detail' => (string) ($context['education_label'] ?? 'Not specified')],
            'certification' => ['label' => 'Certification impact', 'pct' => round($certImpact * 100, 1), 'detail' => $certCount . ' certifications'],
            'location' => ['label' => 'Location impact', 'pct' => round($locationImpact * 100, 1), 'detail' => $location],
            'industry' => ['label' => 'Industry impact', 'pct' => round($industryImpact * 100, 1), 'detail' => $industry],
        ];

        $analysis = [
            'explanation' => $this->explanation($careerLevel, $title, $location, $industry, $predicted, $currency, $confidence),
            'impacts' => $impacts,
            'negotiation_tips' => $this->negotiationTips($careerLevel, $recommended, $market, $currency),
            'signals' => [
                'skill_count' => $skillCount,
                'cert_count' => $certCount,
                'years' => $years,
                'premium_skills' => array_values(array_slice($context['premium_skills'] ?? [], 0, 8)),
                'market_sample_count' => (int) ($context['market_sample_count'] ?? 0),
            ],
            'period' => 'year',
        ];

        return new SalaryPredictionDTO(
            0,
            $resumeId,
            $userId,
            $currency,
            $predicted,
            $min,
            $max,
            $market,
            $recommended,
            $confidence,
            $careerLevel,
            $title !== '' ? $title : 'Professional',
            $location,
            $industry !== '' ? $industry : 'General',
            $analysis,
            SalaryIntelligenceVersion::CURRENT,
            '',
        );
    }

    private function careerLevel(float $years, string $title): string
    {
        $t = mb_strtolower($title);
        if (str_contains($t, 'intern') || str_contains($t, 'trainee') || $years < 1) {
            return 'Entry';
        }
        if (str_contains($t, 'chief') || str_contains($t, 'director') || str_contains($t, 'vp ') || $years >= 12) {
            return 'Executive';
        }
        if (str_contains($t, 'lead') || str_contains($t, 'senior') || str_contains($t, 'manager') || $years >= 7) {
            return 'Senior';
        }
        if ($years >= 3) {
            return 'Mid';
        }

        return 'Junior';
    }

    private function baseByLevel(string $level, string $currency): float
    {
        $usd = match ($level) {
            'Entry' => 22000.0,
            'Junior' => 32000.0,
            'Mid' => 48000.0,
            'Senior' => 72000.0,
            'Executive' => 110000.0,
            default => 40000.0,
        };

        return match ($currency) {
            'LKR' => $usd * 300.0,
            'AED' => $usd * 3.67,
            'EUR' => $usd * 0.92,
            'GBP' => $usd * 0.79,
            'INR' => $usd * 83.0,
            default => $usd,
        };
    }

    /**
     * @param  list<string>  $premium
     */
    private function skillImpact(int $skillCount, array $premium): float
    {
        $base = min(0.22, $skillCount * 0.012);
        $premiumBoost = min(0.12, count($premium) * 0.02);

        return $base + $premiumBoost;
    }

    private function experienceImpact(float $years): float
    {
        return min(0.35, max(0.0, $years) * 0.028);
    }

    private function educationImpact(int $rank): float
    {
        return match (max(0, min(5, $rank))) {
            5 => 0.18,
            4 => 0.12,
            3 => 0.08,
            2 => 0.04,
            1 => 0.02,
            default => 0.0,
        };
    }

    private function certificationImpact(int $count): float
    {
        return min(0.15, max(0, $count) * 0.035);
    }

    private function locationImpact(string $location, string $currency): float
    {
        $l = mb_strtolower($location);
        if (str_contains($l, 'dubai') || str_contains($l, 'uae') || str_contains($l, 'abu dhabi') || $currency === 'AED') {
            return 0.18;
        }
        if (str_contains($l, 'singapore') || str_contains($l, 'london') || str_contains($l, 'new york')) {
            return 0.22;
        }
        if (str_contains($l, 'sri lanka') || str_contains($l, 'colombo') || $currency === 'LKR') {
            return -0.08;
        }
        if (str_contains($l, 'remote')) {
            return 0.05;
        }

        return 0.0;
    }

    private function industryImpact(string $industry): float
    {
        $i = mb_strtolower($industry);
        if (str_contains($i, 'tech') || str_contains($i, 'software') || str_contains($i, 'fintech') || str_contains($i, 'oil')) {
            return 0.14;
        }
        if (str_contains($i, 'health') || str_contains($i, 'nurs') || str_contains($i, 'pharma') || str_contains($i, 'medical')) {
            return 0.10;
        }
        if (str_contains($i, 'educat') || str_contains($i, 'nonprofit') || str_contains($i, 'ngo')) {
            return -0.05;
        }
        if (str_contains($i, 'hospitality') || str_contains($i, 'retail')) {
            return -0.03;
        }

        return 0.02;
    }

    /** @param array<string, mixed> $context */
    private function rawConfidence(array $context): int
    {
        $score = 35;
        if ((float) ($context['years'] ?? 0) > 0) {
            $score += 12;
        }
        if ((int) ($context['skill_count'] ?? 0) >= 3) {
            $score += 12;
        }
        if ((int) ($context['education_rank'] ?? 0) > 0) {
            $score += 10;
        }
        if ((int) ($context['cert_count'] ?? 0) > 0) {
            $score += 8;
        }
        if (trim((string) ($context['job_title'] ?? '')) !== '') {
            $score += 8;
        }
        if (trim((string) ($context['industry'] ?? '')) !== '') {
            $score += 5;
        }
        if ((float) ($context['expected_salary'] ?? 0) > 0 || (float) ($context['current_salary'] ?? 0) > 0) {
            $score += 10;
        }

        return max(0, min(100, $score));
    }

    /** @param array<string, mixed> $context */
    private function confidence(array $context, bool $hasMarket): int
    {
        $score = $this->rawConfidence($context);
        if ($hasMarket) {
            $score = min(100, $score + 12);
        }

        return $score;
    }

    private function explanation(
        string $level,
        string $title,
        string $location,
        string $industry,
        float $predicted,
        string $currency,
        int $confidence,
    ): string {
        return sprintf(
            'Estimated annual market value for a %s %s in %s (%s) is about %s %s. Confidence %d/100 based on resume signals and published job salary bands where available.',
            mb_strtolower($level),
            $title,
            $location !== '' ? $location : 'your target market',
            $industry !== '' ? $industry : 'general industry',
            $currency,
            number_format($predicted, 0),
            $confidence
        );
    }

    /**
     * @return list<string>
     */
    private function negotiationTips(string $level, float $target, float $market, string $currency): array
    {
        $tips = [
            'Anchor near ' . $currency . ' ' . number_format($target, 0) . ' and justify with measurable outcomes.',
            'Compare offers to the market average (' . $currency . ' ' . number_format($market, 0) . ') before accepting.',
            'Negotiate total compensation: base, allowances, visa support, and review cycle.',
        ];
        if ($level === 'Senior' || $level === 'Executive') {
            $tips[] = 'Request a written scope and promotion path tied to the upper band.';
        } else {
            $tips[] = 'Highlight scarce skills and certifications to defend the recommended target.';
        }
        $tips[] = 'Avoid disclosing your exact current salary; share a researched range instead.';

        return $tips;
    }

    private function roundMoney(float $value): float
    {
        if ($value < 1000) {
            return round(max(0, $value), 2);
        }

        return (float) (round($value / 100) * 100);
    }
}

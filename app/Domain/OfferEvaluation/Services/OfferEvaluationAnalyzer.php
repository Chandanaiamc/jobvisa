<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\OfferEvaluation\Services;

use JobVisa\App\Domain\OfferEvaluation\DTO\OfferEvaluationAnalysisDTO;
use JobVisa\App\Domain\OfferEvaluation\Support\OfferEvaluationVersion;

/**
 * Deterministic offer evaluation — no external AI APIs.
 */
final class OfferEvaluationAnalyzer
{
    /**
     * @param  array<string, mixed>  $offer
     * @param  array<string, mixed>  $context
     */
    public function evaluate(int $resumeId, int $userId, array $offer, array $context): OfferEvaluationAnalysisDTO
    {
        $base = (float) ($offer['base_salary'] ?? 0);
        $bonus = (float) ($offer['bonus'] ?? 0);
        $equity = (float) ($offer['equity_value'] ?? 0);
        $totalComp = $base + $bonus + ($equity * 0.25);
        $currency = (string) ($offer['currency'] ?? 'USD');
        $market = (float) ($context['market_salary'] ?? 0);
        $marketMin = (float) ($context['market_min'] ?? ($market > 0 ? $market * 0.86 : 0));
        $marketMax = (float) ($context['market_max'] ?? ($market > 0 ? $market * 1.14 : 0));
        $benefits = is_array($offer['benefits'] ?? null) ? $offer['benefits'] : [];
        $workMode = (string) ($offer['work_mode'] ?? 'onsite');
        $title = (string) ($offer['job_title'] ?? 'Role');
        $company = (string) ($offer['company_name'] ?? '');

        $compensation = $this->scoreCompensation($totalComp, $base, $market, $marketMin, $marketMax);
        $benefitsScore = $this->scoreBenefits($benefits, $workMode, $offer);
        $growth = $this->scoreGrowth($title, $context, $offer);
        $lifestyle = $this->scoreLifestyle($workMode, (string) ($offer['location'] ?? ''), $context);
        $risk = $this->scoreRisk($offer, $context, $compensation);

        $overall = (int) round(
            ($compensation * 0.35)
            + ($benefitsScore * 0.20)
            + ($growth * 0.20)
            + ($lifestyle * 0.15)
            + ((100 - $risk) * 0.10)
        );
        $overall = max(0, min(100, $overall));

        $recommendation = match (true) {
            $overall >= 75 && $compensation >= 65 => OfferEvaluationVersion::REC_ACCEPT,
            $overall < 45 || $compensation < 40 => OfferEvaluationVersion::REC_DECLINE,
            default => OfferEvaluationVersion::REC_NEGOTIATE,
        };

        $counter = $this->counterOffer($base, $bonus, $market, $marketMax, $currency, $recommendation);
        $pros = $this->pros($compensation, $benefitsScore, $growth, $lifestyle, $benefits, $workMode);
        $cons = $this->cons($compensation, $benefitsScore, $growth, $risk, $market, $base);
        $talkingPoints = $this->negotiationPoints($counter, $market, $context, $benefits);
        $checklist = $this->decisionChecklist($recommendation, $offer);

        $analysis = [
            'headline' => sprintf('Offer evaluation: %s%s', $title, $company !== '' ? ' @ ' . $company : ''),
            'summary' => sprintf(
                'Overall %d/100 — recommendation: %s. Total estimated comp %s %s vs market mid %s.',
                $overall,
                strtoupper($recommendation),
                number_format($totalComp, 0),
                $currency,
                $market > 0 ? number_format($market, 0) . ' ' . $currency : 'n/a'
            ),
            'offer_snapshot' => [
                'base_salary' => $base,
                'bonus' => $bonus,
                'equity_value' => $equity,
                'total_comp_estimate' => round($totalComp, 2),
                'currency' => $currency,
                'location' => (string) ($offer['location'] ?? ''),
                'work_mode' => $workMode,
                'benefits' => $benefits,
                'notes' => (string) ($offer['notes'] ?? ''),
                'contract_months' => (int) ($offer['contract_months'] ?? 0),
            ],
            'market_comparison' => [
                'market_mid' => $market,
                'market_min' => $marketMin,
                'market_max' => $marketMax,
                'vs_market_pct' => $market > 0 ? round((($base - $market) / $market) * 100, 1) : null,
                'position_label' => $this->marketPositionLabel($base, $marketMin, $market, $marketMax),
            ],
            'dimension_scores' => [
                'compensation' => $compensation,
                'benefits' => $benefitsScore,
                'growth' => $growth,
                'lifestyle' => $lifestyle,
                'risk' => $risk,
            ],
            'recommendation' => $recommendation,
            'recommendation_label' => match ($recommendation) {
                OfferEvaluationVersion::REC_ACCEPT => 'Strong offer — lean accept (still confirm details)',
                OfferEvaluationVersion::REC_DECLINE => 'Below target — decline or reopen later',
                default => 'Negotiate before deciding',
            },
            'pros' => $pros,
            'cons' => $cons,
            'counter_offer' => $counter,
            'negotiation_talking_points' => $talkingPoints,
            'decision_checklist' => $checklist,
            'signals' => [
                'resume_overall' => (int) ($context['resume_overall'] ?? 0),
                'gap_percentage' => (int) ($context['gap_percentage'] ?? 0),
                'portfolio_strength' => (int) ($context['portfolio_strength'] ?? 0),
                'mock_overall' => (int) ($context['mock_overall'] ?? 0),
                'copilot_score' => (int) ($context['copilot_score'] ?? 0),
            ],
        ];

        return new OfferEvaluationAnalysisDTO(
            0,
            $resumeId,
            $userId,
            isset($offer['job_id']) && (int) $offer['job_id'] > 0 ? (int) $offer['job_id'] : null,
            $company,
            $title,
            $currency,
            $base,
            $overall,
            $compensation,
            $benefitsScore,
            $growth,
            $lifestyle,
            $recommendation,
            $analysis,
            OfferEvaluationVersion::CURRENT,
            '',
        );
    }

    private function scoreCompensation(float $total, float $base, float $market, float $min, float $max): int
    {
        if ($market <= 0) {
            return 55;
        }
        $ratio = $base / $market;
        $score = match (true) {
            $ratio >= 1.12 => 92,
            $ratio >= 1.05 => 84,
            $ratio >= 0.98 => 76,
            $ratio >= 0.90 => 64,
            $ratio >= 0.80 => 50,
            $ratio >= 0.70 => 38,
            default => 25,
        };
        if ($total > $base * 1.1) {
            $score = min(100, $score + 6);
        }
        if ($max > 0 && $base >= $max) {
            $score = min(100, $score + 4);
        }
        if ($min > 0 && $base < $min * 0.9) {
            $score = max(0, $score - 8);
        }

        return max(0, min(100, $score));
    }

    /**
     * @param  list<string>  $benefits
     * @param  array<string, mixed>  $offer
     */
    private function scoreBenefits(array $benefits, string $workMode, array $offer): int
    {
        $score = 35;
        $normalized = array_map(static fn (string $b): string => mb_strtolower($b), $benefits);
        $valuable = [
            'health' => 10,
            'medical' => 10,
            'visa' => 12,
            'sponsorship' => 12,
            'housing' => 10,
            'relocation' => 8,
            'bonus' => 6,
            'pf' => 5,
            'provident' => 5,
            'insurance' => 7,
            'remote' => 6,
            'learning' => 5,
            'education' => 5,
            'leave' => 4,
            'transport' => 4,
        ];
        foreach ($normalized as $b) {
            foreach ($valuable as $needle => $pts) {
                if (str_contains($b, $needle)) {
                    $score += $pts;
                    break;
                }
            }
        }
        if ($workMode === 'remote') {
            $score += 10;
        } elseif ($workMode === 'hybrid') {
            $score += 6;
        }
        if ((float) ($offer['bonus'] ?? 0) > 0) {
            $score += 5;
        }
        if ((float) ($offer['equity_value'] ?? 0) > 0) {
            $score += 5;
        }

        return max(0, min(100, $score + min(15, count($benefits) * 3)));
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $offer
     */
    private function scoreGrowth(string $title, array $context, array $offer): int
    {
        $score = 50;
        $t = mb_strtolower($title);
        if (str_contains($t, 'senior') || str_contains($t, 'lead') || str_contains($t, 'principal')) {
            $score += 15;
        }
        if (str_contains($t, 'intern') || str_contains($t, 'trainee')) {
            $score -= 10;
        }
        if ((int) ($context['portfolio_strength'] ?? 0) >= 50) {
            $score += 8;
        }
        if ((int) ($context['gap_percentage'] ?? 0) >= 40) {
            $score -= 8;
        } elseif ((int) ($context['gap_percentage'] ?? 0) <= 20) {
            $score += 8;
        }
        if ((int) ($offer['contract_months'] ?? 0) > 0 && (int) $offer['contract_months'] < 6) {
            $score -= 10;
        }
        $goal = mb_strtolower((string) ($context['career_goal'] ?? ''));
        if ($goal !== '') {
            foreach (preg_split('/\s+/u', $goal) ?: [] as $token) {
                if (mb_strlen($token) >= 4 && str_contains($t, $token)) {
                    $score += 5;
                    break;
                }
            }
        }

        return max(0, min(100, $score));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function scoreLifestyle(string $workMode, string $location, array $context): int
    {
        $score = 50;
        $score += match ($workMode) {
            'remote' => 20,
            'hybrid' => 12,
            default => 0,
        };
        $preferred = is_array($context['preferred_locations'] ?? null) ? $context['preferred_locations'] : [];
        $loc = mb_strtolower($location);
        foreach ($preferred as $p) {
            $p = mb_strtolower(trim((string) $p));
            if ($p !== '' && ($loc === '' || str_contains($loc, $p) || str_contains($p, $loc))) {
                $score += 12;
                break;
            }
        }
        if (str_contains($loc, 'remote')) {
            $score += 8;
        }

        return max(0, min(100, $score));
    }

    /**
     * @param  array<string, mixed>  $offer
     * @param  array<string, mixed>  $context
     */
    private function scoreRisk(array $offer, array $context, int $compensation): int
    {
        $risk = 30;
        if ($compensation < 45) {
            $risk += 25;
        }
        if ((int) ($offer['contract_months'] ?? 0) > 0 && (int) $offer['contract_months'] < 12) {
            $risk += 15;
        }
        if ((int) ($context['resume_overall'] ?? 0) < 50) {
            $risk += 10;
        }
        if ((string) ($offer['company_name'] ?? '') === '') {
            $risk += 5;
        }
        if ((float) ($offer['base_salary'] ?? 0) <= 0) {
            $risk += 20;
        }

        return max(0, min(100, $risk));
    }

    /**
     * @return array<string, mixed>
     */
    private function counterOffer(
        float $base,
        float $bonus,
        float $market,
        float $marketMax,
        string $currency,
        string $recommendation,
    ): array {
        $target = $market > 0 ? max($base * 1.08, $market * 1.02) : $base * 1.1;
        if ($marketMax > 0) {
            $target = min($target, $marketMax * 1.02);
        }
        $target = round($target, 0);
        $bonusAsk = $bonus > 0 ? round($bonus * 1.15, 0) : round($base * 0.1, 0);

        return [
            'ask_base' => $target,
            'ask_bonus' => $bonusAsk,
            'currency' => $currency,
            'stretch_base' => round($target * 1.05, 0),
            'walk_away_floor' => $market > 0 ? round($market * 0.88, 0) : round($base * 0.95, 0),
            'focus' => $recommendation === OfferEvaluationVersion::REC_ACCEPT
                ? 'Confirm benefits and start date; optional light polish on bonus'
                : 'Lead with base salary and one high-value benefit',
        ];
    }

    /**
     * @param  list<string>  $benefits
     * @return list<string>
     */
    private function pros(int $comp, int $benefitsScore, int $growth, int $lifestyle, array $benefits, string $workMode): array
    {
        $pros = [];
        if ($comp >= 70) {
            $pros[] = 'Compensation compares favorably to market signals';
        }
        if ($benefitsScore >= 65) {
            $pros[] = 'Benefits package shows meaningful coverage';
        }
        if ($growth >= 65) {
            $pros[] = 'Role title/path supports career progression';
        }
        if ($lifestyle >= 65) {
            $pros[] = 'Work mode/location fit looks sustainable';
        }
        if ($workMode === 'remote' || $workMode === 'hybrid') {
            $pros[] = ucfirst($workMode) . ' flexibility improves lifestyle score';
        }
        if ($benefits !== []) {
            $pros[] = 'Documented benefits: ' . implode(', ', array_slice($benefits, 0, 4));
        }
        if ($pros === []) {
            $pros[] = 'Offer creates a concrete negotiation baseline';
        }

        return $pros;
    }

    /**
     * @return list<string>
     */
    private function cons(int $comp, int $benefitsScore, int $growth, int $risk, float $market, float $base): array
    {
        $cons = [];
        if ($comp < 60) {
            $cons[] = 'Base pay trails market mid-point — prioritize salary negotiation';
        }
        if ($benefitsScore < 55) {
            $cons[] = 'Benefits appear thin; request health/visa/housing clarity in writing';
        }
        if ($growth < 55) {
            $cons[] = 'Growth path is unclear relative to your target career goal';
        }
        if ($risk >= 50) {
            $cons[] = 'Elevated risk signals (term length, missing company detail, or weak pay)';
        }
        if ($market > 0 && $base < $market * 0.9) {
            $cons[] = 'Offer is more than 10% below estimated market mid';
        }
        if ($cons === []) {
            $cons[] = 'Still verify contract clauses, notice period and probation terms';
        }

        return $cons;
    }

    /**
     * @param  array<string, mixed>  $counter
     * @param  array<string, mixed>  $context
     * @param  list<string>  $benefits
     * @return list<string>
     */
    private function negotiationPoints(array $counter, float $market, array $context, array $benefits): array
    {
        $points = [];
        $points[] = sprintf(
            'Anchor base at %s %s with stretch %s (walk-away near %s).',
            number_format((float) $counter['ask_base'], 0),
            (string) $counter['currency'],
            number_format((float) $counter['stretch_base'], 0),
            number_format((float) $counter['walk_away_floor'], 0)
        );
        if ($market > 0) {
            $points[] = 'Cite salary intelligence market range as the objective rationale.';
        }
        if ((int) ($context['portfolio_strength'] ?? 0) >= 50) {
            $points[] = 'Reference portfolio proof points to justify senior compensation.';
        }
        if ((int) ($context['mock_overall'] ?? 0) >= 65) {
            $points[] = 'Interview readiness is solid — negotiate confidently without over-explaining.';
        }
        $hasVisa = false;
        foreach ($benefits as $b) {
            if (str_contains(mb_strtolower($b), 'visa') || str_contains(mb_strtolower($b), 'sponsor')) {
                $hasVisa = true;
                break;
            }
        }
        if (!$hasVisa) {
            $points[] = 'If relocation is required, request visa/sponsorship and relocation support explicitly.';
        }
        $points[] = 'Ask for written confirmation of bonus structure, probation, and notice period.';

        return $points;
    }

    /**
     * @param  array<string, mixed>  $offer
     * @return list<string>
     */
    private function decisionChecklist(string $recommendation, array $offer): array
    {
        return [
            'Confirm total compensation in writing (base + bonus + allowances)',
            'Validate benefits start dates and eligibility',
            'Review probation, notice period and non-compete clauses',
            $recommendation === OfferEvaluationVersion::REC_NEGOTIATE
                ? 'Send a polite counter with 1–2 must-have asks within 48 hours'
                : ($recommendation === OfferEvaluationVersion::REC_ACCEPT
                    ? 'Accept only after unresolved questions are clarified'
                    : 'Decline courteously and keep the door open for a revised package'),
            ((string) ($offer['location'] ?? '') !== '' ? 'Assess relocation cost vs net gain' : 'Confirm remote/hybrid expectations with manager'),
        ];
    }

    private function marketPositionLabel(float $base, float $min, float $mid, float $max): string
    {
        if ($mid <= 0) {
            return 'Insufficient market data';
        }
        if ($max > 0 && $base >= $max) {
            return 'Above market max';
        }
        if ($base >= $mid) {
            return 'At or above market mid';
        }
        if ($min > 0 && $base >= $min) {
            return 'Within market band (below mid)';
        }

        return 'Below market band';
    }
}

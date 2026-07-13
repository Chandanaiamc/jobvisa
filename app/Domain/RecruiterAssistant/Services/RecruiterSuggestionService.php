<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\RecruiterAssistant\Services;

use JobVisa\App\Domain\RecruiterAssistant\DTO\RecruiterSearchCriteria;

/**
 * Contextual recruiter suggestions (deterministic).
 */
final class RecruiterSuggestionService
{
    /**
     * @param  list<array<string, mixed>>  $results
     * @param  list<array{label: string, count: int}>  $skillGaps
     * @return list<array{code: string, title: string, query: string, reason: string}>
     */
    public function suggest(RecruiterSearchCriteria $criteria, array $results, array $skillGaps = []): array
    {
        $out = [];

        $out[] = [
            'code' => 'TOP_MATCH',
            'title' => 'Top AI match candidates',
            'query' => 'candidates with match above 70',
            'reason' => 'Surface applicants with the strongest job-match alignment.',
        ];

        $out[] = [
            'code' => 'INTERVIEW_READY',
            'title' => 'Interview-ready shortlist',
            'query' => 'interview ready candidates',
            'reason' => 'Ranking ≥70 and match ≥55 thresholds.',
        ];

        if ($criteria->skills === [] && $skillGaps !== []) {
            $gap = (string) ($skillGaps[0]['label'] ?? '');
            if ($gap !== '') {
                $out[] = [
                    'code' => 'GAP_SKILL',
                    'title' => 'Search for ' . $gap,
                    'query' => 'candidates with ' . $gap,
                    'reason' => 'This skill appears frequently as a gap in your pipeline.',
                ];
            }
        }

        if ($criteria->minExperienceYears === null) {
            $out[] = [
                'code' => 'EXP_FILTER',
                'title' => 'Experienced applicants (5+ years)',
                'query' => 'candidates with 5 years experience',
                'reason' => 'Filter for mid/senior tenure.',
            ];
        }

        if ($results !== [] && count($results) > 5) {
            $out[] = [
                'code' => 'NARROW_MATCH',
                'title' => 'Narrow to stronger matches',
                'query' => trim($criteria->rawQuery . ' match above 60'),
                'reason' => 'Your last search returned many results — raise the match floor.',
            ];
        }

        if ($results === []) {
            $out[] = [
                'code' => 'BROADEN',
                'title' => 'Broaden search',
                'query' => 'top ranked candidates',
                'reason' => 'No matches for the current filters — try a broader query.',
            ];
        }

        return array_slice($out, 0, 6);
    }
}

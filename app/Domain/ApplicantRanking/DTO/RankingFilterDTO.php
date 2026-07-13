<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\ApplicantRanking\DTO;

/**
 * Validated filter/sort options for employer ranking UI.
 */
final class RankingFilterDTO
{
    public const SORT_OVERALL = 'overall';
    public const SORT_MATCH = 'match';
    public const SORT_RESUME = 'resume';
    public const SORT_APPLIED = 'applied';
    public const SORT_RANK = 'rank';

    /**
     * @param  list<string>  $statuses
     */
    public function __construct(
        public readonly array $statuses = [],
        public readonly ?int $minOverall = null,
        public readonly string $sort = self::SORT_RANK,
        public readonly string $direction = 'desc',
        public readonly int $top = 50,
        public readonly string $q = '',
    ) {
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public static function fromInput(array $input): self
    {
        $allowedStatus = ['submitted', 'reviewing', 'shortlisted', 'rejected', 'hired', 'withdrawn'];
        $statuses = [];
        $rawStatus = $input['status'] ?? $input['statuses'] ?? null;
        if (is_string($rawStatus) && $rawStatus !== '' && $rawStatus !== 'all') {
            foreach (explode(',', $rawStatus) as $s) {
                $s = strtolower(trim($s));
                if (in_array($s, $allowedStatus, true)) {
                    $statuses[] = $s;
                }
            }
        } elseif (is_array($rawStatus)) {
            foreach ($rawStatus as $s) {
                $s = strtolower(trim((string) $s));
                if (in_array($s, $allowedStatus, true)) {
                    $statuses[] = $s;
                }
            }
        }

        $sort = strtolower(trim((string) ($input['sort'] ?? self::SORT_RANK)));
        $allowedSort = [self::SORT_OVERALL, self::SORT_MATCH, self::SORT_RESUME, self::SORT_APPLIED, self::SORT_RANK];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = self::SORT_RANK;
        }

        $dir = strtolower(trim((string) ($input['dir'] ?? $input['direction'] ?? 'desc')));
        if (!in_array($dir, ['asc', 'desc'], true)) {
            $dir = 'desc';
        }

        $min = isset($input['min_score']) && $input['min_score'] !== ''
            ? max(0, min(100, (int) $input['min_score']))
            : null;

        $top = max(1, min(200, (int) ($input['top'] ?? 50)));
        $q = trim((string) ($input['q'] ?? ''));

        return new self(
            statuses: array_values(array_unique($statuses)),
            minOverall: $min,
            sort: $sort,
            direction: $dir,
            top: $top,
            q: mb_substr($q, 0, 100),
        );
    }
}

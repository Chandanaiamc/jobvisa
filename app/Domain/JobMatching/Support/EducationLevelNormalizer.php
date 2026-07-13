<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\JobMatching\Support;

/**
 * Normalize free-text education levels for deterministic comparison.
 */
final class EducationLevelNormalizer
{
    /**
     * Rank 0 = unknown/none, higher = more advanced.
     */
    public static function rank(?string $value): int
    {
        $v = mb_strtolower(trim((string) $value));
        if ($v === '') {
            return 0;
        }

        return match (true) {
            str_contains($v, 'phd') || str_contains($v, 'doctor') => 6,
            str_contains($v, 'master') || str_contains($v, 'msc') || str_contains($v, 'mba') || str_contains($v, 'postgrad') => 5,
            str_contains($v, 'bachelor') || str_contains($v, 'degree') || str_contains($v, 'bsc') || str_contains($v, 'ba ') || $v === 'ba' || str_contains($v, 'undergraduate') => 4,
            str_contains($v, 'diploma') || str_contains($v, 'hnc') || str_contains($v, 'hnd') || str_contains($v, 'associate') => 3,
            str_contains($v, 'a-level') || str_contains($v, 'advanced level') || str_contains($v, 'high school') || str_contains($v, 'secondary') || str_contains($v, 'ol') || str_contains($v, 'al') => 2,
            str_contains($v, 'certificate') || str_contains($v, 'vocational') => 2,
            default => 1,
        };
    }

    public static function label(int $rank): string
    {
        return match ($rank) {
            6 => 'Doctorate',
            5 => 'Master\'s',
            4 => 'Bachelor\'s',
            3 => 'Diploma',
            2 => 'Secondary / Certificate',
            1 => 'Other',
            default => 'Not specified',
        };
    }
}

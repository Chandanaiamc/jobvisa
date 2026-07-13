<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Intelligence\Support;

/**
 * Human-readable strength bands for overall intelligence score.
 */
final class StrengthLevel
{
    public const NEEDS_IMPROVEMENT = 'needs_improvement';
    public const BASIC = 'basic';
    public const GOOD = 'good';
    public const STRONG = 'strong';
    public const EXCELLENT = 'excellent';

    public const LABELS = [
        self::NEEDS_IMPROVEMENT => 'Needs improvement',
        self::BASIC => 'Basic',
        self::GOOD => 'Good',
        self::STRONG => 'Strong',
        self::EXCELLENT => 'Excellent',
    ];

    public static function fromScore(int $score): string
    {
        $score = max(0, min(100, $score));

        return match (true) {
            $score >= 90 => self::EXCELLENT,
            $score >= 75 => self::STRONG,
            $score >= 55 => self::GOOD,
            $score >= 30 => self::BASIC,
            default => self::NEEDS_IMPROVEMENT,
        };
    }

    public static function label(string $level): string
    {
        return self::LABELS[$level] ?? self::LABELS[self::NEEDS_IMPROVEMENT];
    }
}

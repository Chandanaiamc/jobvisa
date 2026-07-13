<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Performance\Services;

use JobVisa\App\Logging\Logger;

/**
 * Request-scoped SQL timing / budget tracker.
 */
final class QueryProfiler
{
    private static ?self $instance = null;

    private int $count = 0;

    private float $totalMs = 0.0;

    /** @var list<array{sql: string, ms: float}> */
    private array $slow = [];

    public function __construct(
        private readonly bool $enabled,
        private readonly float $slowMs,
        private readonly int $budget,
    ) {
    }

    public static function boot(self $profiler): void
    {
        self::$instance = $profiler;
    }

    public static function instance(): ?self
    {
        return self::$instance;
    }

    public function record(string $sql, float $elapsedMs): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->count++;
        $this->totalMs += $elapsedMs;

        if ($elapsedMs >= $this->slowMs) {
            $normalized = preg_replace('/\s+/', ' ', trim($sql)) ?? $sql;
            $entry = [
                'sql' => mb_substr($normalized, 0, 280),
                'ms' => round($elapsedMs, 2),
            ];
            $this->slow[] = $entry;
            if (count($this->slow) <= 20) {
                Logger::warning('slow_query', $entry);
            }
        }
    }

    public static function observe(string $sql, callable $execute): mixed
    {
        $profiler = self::$instance;
        if ($profiler === null || !$profiler->enabled) {
            return $execute();
        }

        $start = hrtime(true);
        try {
            return $execute();
        } finally {
            $elapsed = (hrtime(true) - $start) / 1e6;
            $profiler->record($sql, $elapsed);
        }
    }

    public function count(): int
    {
        return $this->count;
    }

    public function totalMs(): float
    {
        return round($this->totalMs, 2);
    }

    /**
     * @return list<array{sql: string, ms: float}>
     */
    public function slowQueries(): array
    {
        return $this->slow;
    }

    public function overBudget(): bool
    {
        return $this->count > $this->budget;
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        return [
            'enabled' => $this->enabled,
            'count' => $this->count,
            'total_ms' => $this->totalMs(),
            'budget' => $this->budget,
            'over_budget' => $this->overBudget(),
            'slow_count' => count($this->slow),
            'slow' => array_slice($this->slow, 0, 10),
        ];
    }
}

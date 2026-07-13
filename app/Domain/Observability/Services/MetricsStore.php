<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Observability\Services;

/**
 * Lightweight file metrics (counters + latency totals) for ops dashboards.
 */
final class MetricsStore
{
    public function __construct(
        private readonly string $directory,
        private readonly bool $enabled,
    ) {
        if (!is_dir($this->directory)) {
            @mkdir($this->directory, 0755, true);
        }
    }

    public function enabled(): bool
    {
        return $this->enabled;
    }

    public function increment(string $key, int $by = 1): void
    {
        if (!$this->enabled || $by === 0) {
            return;
        }
        $this->mutate(static function (array $data) use ($key, $by): array {
            $data['counters'][$key] = (int) ($data['counters'][$key] ?? 0) + $by;

            return $data;
        });
    }

    public function observeLatency(string $key, float $ms): void
    {
        if (!$this->enabled) {
            return;
        }
        $this->mutate(static function (array $data) use ($key, $ms): array {
            $bucket = $data['latency'][$key] ?? ['count' => 0, 'total_ms' => 0.0, 'max_ms' => 0.0];
            $bucket['count'] = (int) $bucket['count'] + 1;
            $bucket['total_ms'] = (float) $bucket['total_ms'] + $ms;
            $bucket['max_ms'] = max((float) $bucket['max_ms'], $ms);
            $data['latency'][$key] = $bucket;

            return $data;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        $data = $this->read();
        $latency = [];
        foreach (($data['latency'] ?? []) as $key => $row) {
            if (!is_array($row)) {
                continue;
            }
            $count = max(1, (int) ($row['count'] ?? 0));
            $total = (float) ($row['total_ms'] ?? 0);
            $latency[$key] = [
                'count' => (int) ($row['count'] ?? 0),
                'avg_ms' => round($total / $count, 2),
                'max_ms' => round((float) ($row['max_ms'] ?? 0), 2),
                'total_ms' => round($total, 2),
            ];
        }

        return [
            'counters' => $data['counters'] ?? [],
            'latency' => $latency,
            'updated_at' => $data['updated_at'] ?? null,
        ];
    }

    public function reset(): void
    {
        $path = $this->path();
        if (is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * @param  callable(array<string, mixed>): array<string, mixed>  $mutator
     */
    private function mutate(callable $mutator): void
    {
        $path = $this->path();
        $fh = @fopen($path, 'c+');
        if ($fh === false) {
            return;
        }
        try {
            if (!flock($fh, LOCK_EX)) {
                return;
            }
            $raw = stream_get_contents($fh);
            $data = is_string($raw) && $raw !== '' ? json_decode($raw, true) : [];
            if (!is_array($data)) {
                $data = [];
            }
            if (!isset($data['counters']) || !is_array($data['counters'])) {
                $data['counters'] = [];
            }
            if (!isset($data['latency']) || !is_array($data['latency'])) {
                $data['latency'] = [];
            }
            $data = $mutator($data);
            $data['updated_at'] = gmdate('c');
            $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($json === false) {
                return;
            }
            ftruncate($fh, 0);
            rewind($fh);
            fwrite($fh, $json);
            fflush($fh);
        } finally {
            flock($fh, LOCK_UN);
            fclose($fh);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function read(): array
    {
        $path = $this->path();
        if (!is_file($path)) {
            return ['counters' => [], 'latency' => []];
        }
        $raw = @file_get_contents($path);
        $data = is_string($raw) && $raw !== '' ? json_decode($raw, true) : [];

        return is_array($data) ? $data : ['counters' => [], 'latency' => []];
    }

    private function path(): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . 'metrics-' . date('Y-m-d') . '.json';
    }
}

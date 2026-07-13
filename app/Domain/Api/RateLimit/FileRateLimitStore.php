<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Api\RateLimit;

/**
 * File-backed rate limit buckets (Redis-ready via RateLimitStoreInterface).
 */
final class FileRateLimitStore implements RateLimitStoreInterface
{
    public function __construct(
        private readonly string $directory,
    ) {
        if (!is_dir($this->directory)) {
            @mkdir($this->directory, 0755, true);
        }
    }

    public function hit(string $key, int $decaySeconds): array
    {
        $path = $this->path($key);
        $fh = @fopen($path, 'c+');
        if ($fh === false) {
            return ['attempts' => 1, 'reset_at' => time() + max(1, $decaySeconds)];
        }
        try {
            flock($fh, LOCK_EX);
            $raw = stream_get_contents($fh);
            $data = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
            $now = time();
            if (!is_array($data) || (int) ($data['reset_at'] ?? 0) < $now) {
                $data = ['attempts' => 1, 'reset_at' => $now + max(1, $decaySeconds)];
            } else {
                $data['attempts'] = (int) ($data['attempts'] ?? 0) + 1;
            }
            ftruncate($fh, 0);
            rewind($fh);
            fwrite($fh, (string) json_encode($data));
            fflush($fh);

            return ['attempts' => (int) $data['attempts'], 'reset_at' => (int) $data['reset_at']];
        } finally {
            flock($fh, LOCK_UN);
            fclose($fh);
        }
    }

    public function get(string $key): ?array
    {
        $path = $this->path($key);
        if (!is_file($path)) {
            return null;
        }
        $raw = @file_get_contents($path);
        $data = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($data)) {
            return null;
        }
        if ((int) ($data['reset_at'] ?? 0) < time()) {
            @unlink($path);

            return null;
        }

        return [
            'attempts' => (int) ($data['attempts'] ?? 0),
            'reset_at' => (int) ($data['reset_at'] ?? 0),
        ];
    }

    private function path(string $key): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . hash('sha256', $key) . '.json';
    }
}

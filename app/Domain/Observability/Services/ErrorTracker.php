<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Observability\Services;

/**
 * Ring buffer of recent application errors for ops triage.
 */
final class ErrorTracker
{
    public function __construct(
        private readonly string $path,
        private readonly bool $enabled,
        private readonly int $ringSize,
    ) {
        $dir = dirname($this->path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function record(string $message, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $entry = [
            'at' => gmdate('c'),
            'message' => mb_substr($message, 0, 500),
            'type' => (string) ($context['type'] ?? 'Error'),
            'request_id' => (string) ($context['request_id'] ?? RequestContext::currentId() ?? ''),
            'file' => (string) ($context['file'] ?? ''),
            'line' => (int) ($context['line'] ?? 0),
            'path' => (string) ($context['path'] ?? ($_SERVER['REQUEST_URI'] ?? '')),
            'ip' => (string) ($context['ip'] ?? ''),
        ];

        $fh = @fopen($this->path, 'c+');
        if ($fh === false) {
            return;
        }
        try {
            if (!flock($fh, LOCK_EX)) {
                return;
            }
            $raw = stream_get_contents($fh);
            $list = is_string($raw) && $raw !== '' ? json_decode($raw, true) : [];
            if (!is_array($list)) {
                $list = [];
            }
            $list[] = $entry;
            if (count($list) > $this->ringSize) {
                $list = array_slice($list, -$this->ringSize);
            }
            $json = json_encode(array_values($list), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
     * @return list<array<string, mixed>>
     */
    public function recent(int $limit = 20): array
    {
        if (!is_file($this->path)) {
            return [];
        }
        $raw = @file_get_contents($this->path);
        $list = is_string($raw) && $raw !== '' ? json_decode($raw, true) : [];
        if (!is_array($list)) {
            return [];
        }
        $limit = max(1, min(100, $limit));

        return array_slice(array_values($list), -$limit);
    }

    public function clear(): void
    {
        if (is_file($this->path)) {
            @unlink($this->path);
        }
    }
}

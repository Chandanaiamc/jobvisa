<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Deployment\Services;

/**
 * Append-only deployment audit log (secrets redacted).
 */
final class DeploymentAuditLog
{
    public function directory(): string
    {
        $dir = base_path('storage/deployments');
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        return $dir;
    }

    public function path(): string
    {
        return $this->directory() . DIRECTORY_SEPARATOR . 'audit-' . date('Y-m') . '.jsonl';
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function write(string $event, array $context = []): void
    {
        $line = json_encode([
            'at' => gmdate('c'),
            'event' => $event,
            'context' => $this->redact($context),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($line === false) {
            return;
        }
        @file_put_contents($this->path(), $line . "\n", FILE_APPEND | LOCK_EX);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recent(int $limit = 20): array
    {
        $path = $this->path();
        if (!is_file($path)) {
            return [];
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $lines = array_slice($lines, -max(1, min(100, $limit)));
        $out = [];
        foreach ($lines as $line) {
            $row = json_decode($line, true);
            if (is_array($row)) {
                $out[] = $row;
            }
        }

        return $out;
    }

    /**
     * Persist a full deployment report JSON.
     *
     * @param  array<string, mixed>  $report
     */
    public function saveReport(string $id, array $report): string
    {
        $path = $this->directory() . DIRECTORY_SEPARATOR . 'report-' . $id . '.json';
        @file_put_contents(
            $path,
            (string) json_encode($this->redact($report), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
            LOCK_EX
        );

        return $path;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function redact(array $data): array
    {
        $sensitive = ['password', 'secret', 'token', 'authorization', 'mysql_pwd', 'db_password'];
        $clean = [];
        foreach ($data as $key => $value) {
            $lower = strtolower((string) $key);
            foreach ($sensitive as $needle) {
                if (str_contains($lower, $needle)) {
                    $clean[$key] = '[REDACTED]';
                    continue 2;
                }
            }
            if (is_array($value)) {
                $clean[$key] = $this->redact($value);
            } else {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }
}

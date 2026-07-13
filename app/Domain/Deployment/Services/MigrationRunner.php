<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Deployment\Services;

use App\Core\Database;
use PDO;

/**
 * Ordered SQL migration runner with schema_migrations tracking (idempotent).
 */
final class MigrationRunner
{
    public function __construct(
        private readonly string $migrationsPath = '',
    ) {
    }

    private function path(): string
    {
        if ($this->migrationsPath !== '') {
            return $this->migrationsPath;
        }

        return base_path((string) config('deployment.migrations_path', 'database/migrations'));
    }

    public function ensureTrackingTable(): void
    {
        $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS `schema_migrations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `migration` VARCHAR(191) NOT NULL,
    `batch` INT UNSIGNED NOT NULL DEFAULT 1,
    `applied_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_schema_migrations_migration` (`migration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;
        Database::connection()->exec($sql);
    }

    /**
     * @return list<string> basenames of forward migrations
     */
    public function discover(): array
    {
        $dir = $this->path();
        $files = glob($dir . DIRECTORY_SEPARATOR . '*.sql') ?: [];
        $out = [];
        foreach ($files as $file) {
            $base = basename($file);
            if (str_ends_with($base, '_rollback.sql')) {
                continue;
            }
            if (!preg_match('/^\d{3}_.+\.sql$/', $base)) {
                continue;
            }
            $out[] = $base;
        }
        sort($out, SORT_STRING);

        return $out;
    }

    /**
     * @return list<string>
     */
    public function applied(): array
    {
        $this->ensureTrackingTable();
        $rows = Database::query('SELECT migration FROM schema_migrations ORDER BY migration ASC')->fetchAll(PDO::FETCH_COLUMN);

        return array_values(array_map('strval', $rows ?: []));
    }

    /**
     * @return list<string>
     */
    public function pending(): array
    {
        $applied = array_flip($this->applied());
        $pending = [];
        foreach ($this->discover() as $name) {
            if (!isset($applied[$name])) {
                $pending[] = $name;
            }
        }

        return $pending;
    }

    /**
     * Mark all discovered migrations applied without executing (safe for existing DBs).
     *
     * @return array{ok: bool, marked: int, message: string}
     */
    public function baseline(bool $dryRun = false): array
    {
        $this->ensureTrackingTable();
        $applied = array_flip($this->applied());
        $toMark = [];
        foreach ($this->discover() as $name) {
            if (!isset($applied[$name])) {
                $toMark[] = $name;
            }
        }
        if ($dryRun) {
            return ['ok' => true, 'marked' => count($toMark), 'message' => 'dry_run_baseline', 'migrations' => $toMark];
        }
        $batch = $this->nextBatch();
        $stmt = Database::connection()->prepare(
            'INSERT IGNORE INTO schema_migrations (migration, batch) VALUES (?, ?)'
        );
        foreach ($toMark as $name) {
            $stmt->execute([$name, $batch]);
        }

        return ['ok' => true, 'marked' => count($toMark), 'message' => 'baselined', 'batch' => $batch];
    }

    /**
     * Auto-baseline when tracking is empty but core tables already exist.
     */
    public function maybeAutoBaseline(): void
    {
        $this->ensureTrackingTable();
        if ($this->applied() !== []) {
            return;
        }
        try {
            $exists = Database::query(
                "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'users'"
            )->fetchColumn();
            if ((int) $exists > 0) {
                $this->baseline(false);
            }
        } catch (\Throwable) {
            // leave pending for explicit migrate
        }
    }

    /**
     * @return array{ok: bool, applied: list<string>, pending: list<string>, message: string, dry_run?: bool}
     */
    public function migrate(bool $dryRun = false): array
    {
        $this->maybeAutoBaseline();
        $pending = $this->pending();
        if ($pending === []) {
            return ['ok' => true, 'applied' => [], 'pending' => [], 'message' => 'nothing_to_migrate'];
        }
        if ($dryRun) {
            return [
                'ok' => true,
                'applied' => [],
                'pending' => $pending,
                'message' => 'dry_run_would_migrate',
                'dry_run' => true,
            ];
        }

        $batch = $this->nextBatch();
        $applied = [];
        $pdo = Database::connection();
        foreach ($pending as $name) {
            $file = $this->path() . DIRECTORY_SEPARATOR . $name;
            $sql = @file_get_contents($file);
            if (!is_string($sql) || trim($sql) === '') {
                return [
                    'ok' => false,
                    'applied' => $applied,
                    'pending' => $pending,
                    'message' => 'unreadable:' . $name,
                ];
            }
            try {
                $this->executeSqlFile($pdo, $sql);
                $pdo->prepare('INSERT INTO schema_migrations (migration, batch) VALUES (?, ?)')
                    ->execute([$name, $batch]);
                $applied[] = $name;
            } catch (\Throwable $e) {
                return [
                    'ok' => false,
                    'applied' => $applied,
                    'pending' => $pending,
                    'message' => 'failed:' . $name,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'ok' => true,
            'applied' => $applied,
            'pending' => [],
            'message' => 'migrated',
            'batch' => $batch,
        ];
    }

    /**
     * Roll back the last batch (runs paired *_rollback.sql when present).
     *
     * @return array{ok: bool, rolled_back: list<string>, message: string, dry_run?: bool}
     */
    public function rollbackLast(bool $dryRun = false): array
    {
        $this->ensureTrackingTable();
        $batch = (int) (Database::query('SELECT MAX(batch) FROM schema_migrations')->fetchColumn() ?: 0);
        if ($batch < 1) {
            return ['ok' => true, 'rolled_back' => [], 'message' => 'nothing_to_rollback'];
        }
        $rows = Database::query(
            'SELECT migration FROM schema_migrations WHERE batch = ? ORDER BY migration DESC',
            [$batch]
        )->fetchAll(PDO::FETCH_COLUMN);
        $names = array_values(array_map('strval', $rows ?: []));
        if ($dryRun) {
            return [
                'ok' => true,
                'rolled_back' => [],
                'message' => 'dry_run_would_rollback',
                'migrations' => $names,
                'batch' => $batch,
                'dry_run' => true,
            ];
        }

        $pdo = Database::connection();
        $rolled = [];
        foreach ($names as $name) {
            $rollback = preg_replace('/\.sql$/', '_rollback.sql', $name) ?? ($name . '_rollback.sql');
            $file = $this->path() . DIRECTORY_SEPARATOR . $rollback;
            if (is_file($file)) {
                $sql = (string) file_get_contents($file);
                try {
                    $this->executeSqlFile($pdo, $sql);
                } catch (\Throwable $e) {
                    return [
                        'ok' => false,
                        'rolled_back' => $rolled,
                        'message' => 'rollback_failed:' . $rollback,
                        'error' => $e->getMessage(),
                    ];
                }
            }
            $pdo->prepare('DELETE FROM schema_migrations WHERE migration = ?')->execute([$name]);
            $rolled[] = $name;
        }

        return ['ok' => true, 'rolled_back' => $rolled, 'message' => 'rolled_back', 'batch' => $batch];
    }

    /**
     * @return array{discovered: int, applied: int, pending: list<string>}
     */
    public function status(): array
    {
        $this->maybeAutoBaseline();

        return [
            'discovered' => count($this->discover()),
            'applied' => count($this->applied()),
            'pending' => $this->pending(),
        ];
    }

    private function nextBatch(): int
    {
        $max = (int) (Database::query('SELECT MAX(batch) FROM schema_migrations')->fetchColumn() ?: 0);

        return $max + 1;
    }

    private function executeSqlFile(PDO $pdo, string $sql): void
    {
        // Strip line comments; split on semicolons for multi-statement files.
        $lines = preg_split('/\R/', $sql) ?: [];
        $buf = [];
        foreach ($lines as $line) {
            $trim = ltrim($line);
            if (str_starts_with($trim, '--')) {
                continue;
            }
            $buf[] = $line;
        }
        $clean = implode("\n", $buf);
        $parts = preg_split('/;\s*\n/', $clean) ?: [];
        foreach ($parts as $part) {
            $stmt = trim($part);
            if ($stmt === '' || $stmt === ';') {
                continue;
            }
            $pdo->exec($stmt);
        }
    }
}

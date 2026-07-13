<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Deployment\Services;

use App\Core\Database;
use PDO;

/**
 * Database backup via mysqldump with PDO SQL fallback (never logs credentials).
 */
final class BackupManager
{
    public function directory(): string
    {
        $dir = base_path('storage/backups');
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        return $dir;
    }

    public function resolveMysqldump(): ?string
    {
        $configured = trim((string) config('deployment.mysqldump_path', ''));
        if ($configured !== '' && is_file($configured)) {
            return $configured;
        }

        $candidates = [
            'E:\\localhost\\mysql\\bin\\mysqldump.exe',
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
        ];
        foreach ($candidates as $bin) {
            if (is_file($bin)) {
                return $bin;
            }
        }

        return 'mysqldump';
    }

    /**
     * @return array{ok: bool, path?: string, bytes?: int, message: string, dry_run?: bool, method?: string}
     */
    public function backup(bool $dryRun = false): array
    {
        if (!(bool) config('deployment.backup_enabled', true)) {
            return ['ok' => true, 'message' => 'backup_disabled', 'dry_run' => $dryRun];
        }

        $stamp = gmdate('Ymd_His');
        $path = $this->directory() . DIRECTORY_SEPARATOR . 'jobvisa_db_' . $stamp . '.sql';
        $bin = $this->resolveMysqldump();

        if ($dryRun) {
            return [
                'ok' => true,
                'path' => $path,
                'message' => 'dry_run_would_backup',
                'dry_run' => true,
                'tool' => $bin,
            ];
        }

        $dump = $this->runMysqldump($bin, $path);
        if (!($dump['ok'] ?? false)) {
            $dump = $this->runPdoBackup($path);
        }

        if (!($dump['ok'] ?? false)) {
            @unlink($path);

            return ['ok' => false, 'message' => (string) ($dump['message'] ?? 'backup_failed')];
        }

        $this->prune();

        return [
            'ok' => true,
            'path' => $path,
            'bytes' => (int) filesize($path),
            'message' => 'backup_created',
            'method' => (string) ($dump['method'] ?? 'mysqldump'),
        ];
    }

    /**
     * @return array{ok: bool, message: string, path?: string}
     */
    public function restore(string $backupPath, bool $dryRun = false): array
    {
        if (!is_file($backupPath)) {
            return ['ok' => false, 'message' => 'backup_missing'];
        }

        if ($dryRun) {
            return ['ok' => true, 'message' => 'dry_run_would_restore', 'path' => $backupPath];
        }

        $cli = $this->resolveMysqlCli();
        if ($cli === null) {
            return ['ok' => false, 'message' => 'mysql_cli_not_found', 'path' => $backupPath];
        }

        $host = (string) config('database.host', config('app.db.host', 'localhost'));
        $port = (string) config('database.port', config('app.db.port', '3306'));
        $name = (string) config('database.name', config('app.db.name', 'jobvisa_db'));
        $user = (string) config('database.user', config('app.db.user', 'root'));
        $pass = (string) config('database.password', config('app.db.password', ''));

        $args = [$cli, '-h' . $host, '-P' . $port, '-u' . $user];
        if ($pass !== '') {
            $args[] = '-p' . $pass;
        }
        unset($pass);
        $args[] = $name;

        $descriptors = [
            0 => ['file', $backupPath, 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = @proc_open($args, $descriptors, $pipes, null, null);
        if (!is_resource($process)) {
            return ['ok' => false, 'message' => 'mysql_restore_spawn_failed', 'path' => $backupPath];
        }
        stream_get_contents($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($process);

        return $code === 0
            ? ['ok' => true, 'message' => 'backup_restored', 'path' => $backupPath]
            : ['ok' => false, 'message' => 'restore_failed', 'path' => $backupPath];
    }

    public function latestBackup(): ?string
    {
        $files = glob($this->directory() . DIRECTORY_SEPARATOR . 'jobvisa_db_*.sql') ?: [];
        if ($files === []) {
            return null;
        }
        rsort($files);

        return $files[0];
    }

    /**
     * @return array{ok: bool, message: string, method?: string}
     */
    private function runMysqldump(string $bin, string $path): array
    {
        $host = (string) config('database.host', config('app.db.host', 'localhost'));
        $port = (string) config('database.port', config('app.db.port', '3306'));
        $name = (string) config('database.name', config('app.db.name', 'jobvisa_db'));
        $user = (string) config('database.user', config('app.db.user', 'root'));
        $pass = (string) config('database.password', config('app.db.password', ''));

        $cmd = [$bin, '-h' . $host, '-P' . $port, '-u' . $user];
        if ($pass !== '') {
            $cmd[] = '-p' . $pass;
        }
        unset($pass);
        array_push(
            $cmd,
            '--single-transaction',
            '--routines',
            '--triggers',
            '--result-file=' . $path,
            $name
        );

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = @proc_open($cmd, $descriptors, $pipes, null, null);
        if (!is_resource($process)) {
            return ['ok' => false, 'message' => 'mysqldump_spawn_failed'];
        }
        fclose($pipes[0]);
        stream_get_contents($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($process);

        if ($code !== 0 || !is_file($path) || filesize($path) < 32) {
            @unlink($path);

            return ['ok' => false, 'message' => 'mysqldump_failed'];
        }

        return ['ok' => true, 'message' => 'ok', 'method' => 'mysqldump'];
    }

    /**
     * Portable logical dump when mysqldump is unavailable or fails.
     *
     * @return array{ok: bool, message: string, method?: string}
     */
    private function runPdoBackup(string $path): array
    {
        try {
            $pdo = Database::connection();
            $dbName = (string) config('database.name', config('app.db.name', 'jobvisa_db'));
            $fh = fopen($path, 'wb');
            if ($fh === false) {
                return ['ok' => false, 'message' => 'pdo_backup_open_failed'];
            }

            fwrite($fh, "-- JobVisa.lk PDO logical backup\n");
            fwrite($fh, '-- Generated: ' . gmdate('c') . "\n");
            fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\n\n");

            $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) ?: [];
            foreach ($tables as $table) {
                $table = (string) $table;
                $create = $pdo->query('SHOW CREATE TABLE `' . str_replace('`', '``', $table) . '`')->fetch(PDO::FETCH_NUM);
                if (!is_array($create) || !isset($create[1])) {
                    continue;
                }
                fwrite($fh, 'DROP TABLE IF EXISTS `' . str_replace('`', '``', $table) . "`;\n");
                fwrite($fh, $create[1] . ";\n\n");

                $rows = $pdo->query('SELECT * FROM `' . str_replace('`', '``', $table) . '`');
                if ($rows === false) {
                    continue;
                }
                while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
                    if (!is_array($row) || $row === []) {
                        continue;
                    }
                    $cols = array_map(
                        static fn (string $c): string => '`' . str_replace('`', '``', $c) . '`',
                        array_keys($row)
                    );
                    $vals = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $vals[] = 'NULL';
                        } else {
                            $vals[] = $pdo->quote((string) $value);
                        }
                    }
                    fwrite(
                        $fh,
                        'INSERT INTO `' . str_replace('`', '``', $table) . '` (' . implode(',', $cols) . ') VALUES (' . implode(',', $vals) . ");\n"
                    );
                }
                fwrite($fh, "\n");
            }

            fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
            fclose($fh);

            if (!is_file($path) || filesize($path) < 32) {
                return ['ok' => false, 'message' => 'pdo_backup_empty'];
            }

            return ['ok' => true, 'message' => 'ok', 'method' => 'pdo', 'database' => $dbName];
        } catch (\Throwable) {
            return ['ok' => false, 'message' => 'pdo_backup_failed'];
        }
    }

    private function resolveMysqlCli(): ?string
    {
        $configured = trim((string) config('deployment.mysql_cli_path', ''));
        if ($configured !== '' && is_file($configured)) {
            return $configured;
        }
        $candidates = [
            'E:\\localhost\\mysql\\bin\\mysql.exe',
            'C:\\xampp\\mysql\\bin\\mysql.exe',
            '/usr/bin/mysql',
            '/usr/local/bin/mysql',
            'mysql',
        ];
        foreach ($candidates as $bin) {
            if ($bin === 'mysql') {
                return $bin;
            }
            if (is_file($bin)) {
                return $bin;
            }
        }

        return null;
    }

    private function prune(): void
    {
        $keep = (int) config('deployment.backup_retention', 14);
        $files = glob($this->directory() . DIRECTORY_SEPARATOR . 'jobvisa_db_*.sql') ?: [];
        rsort($files);
        foreach (array_slice($files, $keep) as $old) {
            @unlink($old);
        }
    }
}

<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Testing\Services;

/**
 * Orchestrates enterprise CLI gates and PHPUnit for Release Candidate.
 */
final class QaGateRunner
{
    /**
     * @param list<string>|null $only Gate keys; null = configured defaults
     * @return array{ok: bool, results: array<string, array{ok: bool, output: string, exit_code: int}>}
     */
    public function runEnterpriseGates(?array $only = null, bool $dryRun = false): array
    {
        /** @var array<string, string> $gates */
        $gates = (array) config('testing.gates', []);
        $includeDeployment = (bool) config('testing.run_deployment_gate', false);

        if ($only === null) {
            $only = array_keys($gates);
            if (!$includeDeployment) {
                $only = array_values(array_filter(
                    $only,
                    static fn (string $k): bool => $k !== 'deployment'
                ));
            }
        }

        $results = [];
        $allOk = true;
        $php = PHP_BINARY !== '' ? PHP_BINARY : 'php';

        foreach ($only as $name) {
            $rel = $gates[$name] ?? null;
            if (!is_string($rel) || $rel === '') {
                $results[$name] = ['ok' => false, 'output' => 'unknown_gate', 'exit_code' => 1];
                $allOk = false;
                continue;
            }
            if ($dryRun) {
                $results[$name] = ['ok' => true, 'output' => 'dry_run_skipped', 'exit_code' => 0];
                continue;
            }
            $path = base_path($rel);
            if (!is_file($path)) {
                $results[$name] = ['ok' => false, 'output' => 'missing:' . $rel, 'exit_code' => 1];
                $allOk = false;
                continue;
            }
            $out = [];
            $code = 0;
            exec(escapeshellarg($php) . ' ' . escapeshellarg($path) . ' 2>&1', $out, $code);
            $text = implode("\n", $out);
            $pass = $code === 0 && str_contains($text, 'PASS');
            $results[$name] = [
                'ok' => $pass,
                'output' => mb_substr($text, -800),
                'exit_code' => $code,
            ];
            if (!$pass) {
                $allOk = false;
            }
        }

        return ['ok' => $allOk, 'results' => $results];
    }

    /**
     * @return array{ok: bool, output: string, exit_code: int, skipped: bool}
     */
    public function runPhpUnit(bool $dryRun = false): array
    {
        if (!(bool) config('testing.run_phpunit', true)) {
            return ['ok' => true, 'output' => 'phpunit_disabled', 'exit_code' => 0, 'skipped' => true];
        }

        if ($dryRun) {
            return ['ok' => true, 'output' => 'dry_run_skipped', 'exit_code' => 0, 'skipped' => true];
        }

        $rel = (string) config('testing.phpunit_binary', 'vendor/bin/phpunit');
        $bin = base_path($rel);
        if (DIRECTORY_SEPARATOR === '\\') {
            $bat = $bin . '.bat';
            if (is_file($bat)) {
                $bin = $bat;
            }
        }
        if (!is_file($bin) && !is_file(base_path('vendor/phpunit/phpunit/phpunit'))) {
            return ['ok' => false, 'output' => 'phpunit_missing', 'exit_code' => 1, 'skipped' => false];
        }

        $php = PHP_BINARY !== '' ? PHP_BINARY : 'php';
        $phpunitPhar = base_path('vendor/phpunit/phpunit/phpunit');
        $cmd = is_file($phpunitPhar)
            ? escapeshellarg($php) . ' ' . escapeshellarg($phpunitPhar)
            : escapeshellarg($bin);

        $out = [];
        $code = 0;
        exec($cmd . ' --configuration ' . escapeshellarg(base_path('phpunit.xml')) . ' 2>&1', $out, $code);
        $text = implode("\n", $out);
        $pass = $code === 0;

        return [
            'ok' => $pass,
            'output' => mb_substr($text, -1200),
            'exit_code' => $code,
            'skipped' => false,
        ];
    }
}

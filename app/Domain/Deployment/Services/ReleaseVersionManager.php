<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Deployment\Services;

use JobVisa\App\Domain\Deployment\Support\DeploymentVersion;

/**
 * Stamps and reads application release versions.
 */
final class ReleaseVersionManager
{
    public function directory(): string
    {
        $dir = base_path('storage/releases');
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        return $dir;
    }

    public function currentPath(): string
    {
        return $this->directory() . DIRECTORY_SEPARATOR . 'CURRENT';
    }

    public function current(): ?string
    {
        $path = $this->currentPath();
        if (!is_file($path)) {
            return null;
        }
        $v = trim((string) file_get_contents($path));

        return $v !== '' ? $v : null;
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array{ok: bool, version: string, path: string, dry_run?: bool}
     */
    public function stamp(string $version, array $meta = [], bool $dryRun = false): array
    {
        $version = preg_replace('/[^a-zA-Z0-9.\-_]/', '', $version) ?: DeploymentVersion::CURRENT;
        $metaPath = $this->directory() . DIRECTORY_SEPARATOR . $version . '.json';
        $payload = array_merge([
            'version' => $version,
            'deployment_rules' => DeploymentVersion::CURRENT,
            'stamped_at' => gmdate('c'),
            'php' => PHP_VERSION,
            'env' => (string) config('app.env', 'local'),
        ], $meta);

        if (!$dryRun) {
            @file_put_contents(
                $metaPath,
                (string) json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
                LOCK_EX
            );
            @file_put_contents($this->currentPath(), $version . "\n", LOCK_EX);
        }

        return [
            'ok' => true,
            'version' => $version,
            'path' => $metaPath,
            'dry_run' => $dryRun,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function read(string $version): ?array
    {
        $path = $this->directory() . DIRECTORY_SEPARATOR . $version . '.json';
        if (!is_file($path)) {
            return null;
        }
        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) ? $data : null;
    }
}

<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Deployment\Services;

/**
 * File-based maintenance toggle for deploy windows (also honors APP_MAINTENANCE).
 */
final class MaintenanceModeManager
{
    public function flagPath(): string
    {
        return base_path('storage/framework/maintenance.json');
    }

    public function isActive(): bool
    {
        if ((bool) config('production.maintenance', false)) {
            return true;
        }

        return is_file($this->flagPath());
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array{ok: bool, path: string, active: bool}
     */
    public function enable(array $meta = [], bool $dryRun = false): array
    {
        $path = $this->flagPath();
        $dir = dirname($path);
        if (!$dryRun) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $payload = array_merge([
                'enabled_at' => gmdate('c'),
                'reason' => 'deployment',
                'by' => 'DeploymentManager',
            ], $meta);
            @file_put_contents(
                $path,
                (string) json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
                LOCK_EX
            );
        }

        return ['ok' => true, 'path' => $path, 'active' => true, 'dry_run' => $dryRun];
    }

    /**
     * @return array{ok: bool, path: string, active: bool, restored: bool}
     */
    public function disable(bool $dryRun = false): array
    {
        $path = $this->flagPath();
        $existed = is_file($path);
        if (!$dryRun && $existed) {
            @unlink($path);
        }

        return [
            'ok' => true,
            'path' => $path,
            'active' => $dryRun ? $existed : is_file($path),
            'restored' => !$dryRun && $existed,
            'dry_run' => $dryRun,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function status(): ?array
    {
        if (!is_file($this->flagPath())) {
            return null;
        }
        $raw = @file_get_contents($this->flagPath());
        $data = is_string($raw) ? json_decode($raw, true) : null;

        return is_array($data) ? $data : ['enabled' => true];
    }
}

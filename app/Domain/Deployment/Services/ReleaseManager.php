<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Deployment\Services;

use JobVisa\App\Cache\CacheInterface;
use JobVisa\App\Domain\Deployment\Support\DeploymentVersion;
use JobVisa\App\Repositories\Contracts\LanguageCatalogRepositoryInterface;
use JobVisa\App\Repositories\Contracts\LocationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\SkillCatalogRepositoryInterface;

/**
 * Release-time cache / autoload / asset / version stamping helpers.
 */
final class ReleaseManager
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly ReleaseVersionManager $versions,
        private readonly SkillCatalogRepositoryInterface $skills,
        private readonly LanguageCatalogRepositoryInterface $languages,
        private readonly LocationRepositoryInterface $locations,
    ) {
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function clearCache(bool $dryRun = false): array
    {
        if ($dryRun) {
            return ['ok' => true, 'message' => 'dry_run_clear_cache'];
        }

        return ['ok' => $this->cache->flush(), 'message' => 'cache_flushed'];
    }

    /**
     * @return array{ok: bool, message: string, warmed?: list<string>}
     */
    public function warmCache(bool $dryRun = false): array
    {
        if ($dryRun || !(bool) config('deployment.warm_cache', true)) {
            return ['ok' => true, 'message' => $dryRun ? 'dry_run_warm_cache' : 'warm_skipped'];
        }
        $warmed = [];
        try {
            $this->skills->listActive();
            $warmed[] = 'skills';
            $this->languages->listActive();
            $warmed[] = 'languages';
            $this->locations->listCountries();
            $warmed[] = 'countries';
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'warm_failed', 'warmed' => $warmed];
        }

        return ['ok' => true, 'message' => 'cache_warmed', 'warmed' => $warmed];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function optimizeAutoload(bool $dryRun = false): array
    {
        if ($dryRun || !(bool) config('deployment.optimize_autoload', true)) {
            return ['ok' => true, 'message' => $dryRun ? 'dry_run_optimize' : 'optimize_skipped'];
        }
        $composer = base_path('composer.phar');
        $cmd = is_file($composer)
            ? escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($composer) . ' dump-autoload -o'
            : 'composer dump-autoload -o';
        $out = [];
        $code = 0;
        exec($cmd . ' 2>&1', $out, $code);
        // Non-fatal if composer missing in constrained hosts.
        return [
            'ok' => true,
            'message' => $code === 0 ? 'autoload_optimized' : 'optimize_best_effort',
            'exit_code' => $code,
        ];
    }

    /**
     * @return array{ok: bool, message: string, missing?: list<string>}
     */
    public function verifyAssets(bool $dryRun = false): array
    {
        $required = [
            'public/index.php',
            'public/.htaccess',
            'public/robots.txt',
        ];
        $missing = [];
        foreach ($required as $rel) {
            if (!is_file(base_path($rel))) {
                $missing[] = $rel;
            }
        }
        if ($dryRun) {
            return ['ok' => $missing === [], 'message' => 'dry_run_assets', 'missing' => $missing];
        }

        return [
            'ok' => $missing === [],
            'message' => $missing === [] ? 'assets_ok' : 'assets_missing',
            'missing' => $missing,
        ];
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array{ok: bool, version: string, path: string}
     */
    public function stampRelease(?string $version = null, array $meta = [], bool $dryRun = false): array
    {
        $version = $version ?: ('4.4.0+' . gmdate('YmdHis'));

        return $this->versions->stamp($version, array_merge([
            'rules' => DeploymentVersion::CURRENT,
        ], $meta), $dryRun);
    }
}

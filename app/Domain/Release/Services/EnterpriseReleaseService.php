<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Release\Services;

use JobVisa\App\Domain\Api\Portal\Support\DeveloperPortalVersion;
use JobVisa\App\Domain\Api\Support\ApiVersion;
use JobVisa\App\Domain\Deployment\Services\ReleaseVersionManager;
use JobVisa\App\Domain\Deployment\Support\DeploymentVersion;
use JobVisa\App\Domain\Frontend\Support\FrontendPolishVersion;
use JobVisa\App\Domain\Observability\Support\ObservabilityVersion;
use JobVisa\App\Domain\Performance\Support\PerformanceVersion;
use JobVisa\App\Domain\Production\Support\ProductionReadinessVersion;
use JobVisa\App\Domain\Release\Support\EnterpriseReleaseVersion;
use JobVisa\App\Domain\Security\Support\SecurityHardeningVersion;
use JobVisa\App\Domain\Testing\Support\ReleaseCandidateVersion;

/**
 * Enterprise v1.0.0 release readiness and artifact verification.
 */
final class EnterpriseReleaseService
{
    public function __construct(
        private readonly ReleaseManifestBuilder $manifests,
        private readonly ReleaseVersionManager $versions,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function status(): array
    {
        $artifacts = $this->verifyArtifacts();
        $modules = $this->verifyModules();
        $versionFile = $this->verifyVersionFile();
        $failedArtifacts = array_filter($artifacts, static fn (array $r): bool => !($r['ok'] ?? false));
        $failedModules = array_filter($modules, static fn (array $r): bool => !($r['ok'] ?? false));

        $ok = $failedArtifacts === []
            && $failedModules === []
            && ($versionFile['ok'] ?? false)
            && (bool) config('release.enabled', true);

        return [
            'status' => $ok ? 'ok' : 'failed',
            'version' => EnterpriseReleaseVersion::CURRENT,
            'tag' => EnterpriseReleaseVersion::TAG,
            'released_at' => EnterpriseReleaseVersion::RELEASE_DATE,
            'version_file' => $versionFile,
            'artifacts' => $artifacts,
            'modules' => $modules,
            'composer_aligned' => $this->composerAligned(),
        ];
    }

    /**
     * Ensure manifest on disk matches current build metadata.
     *
     * @return array{ok: bool, path: string, manifest: array<string, mixed>}
     */
    public function ensureManifest(bool $dryRun = false): array
    {
        return $this->manifests->write($dryRun);
    }

    /**
     * Stamp storage/releases CURRENT to product version.
     *
     * @return array{ok: bool, version: string, path: string, dry_run?: bool}
     */
    public function stamp(bool $dryRun = false): array
    {
        return $this->versions->stamp(EnterpriseReleaseVersion::CURRENT, [
            'tag' => EnterpriseReleaseVersion::TAG,
            'release' => 'enterprise_ga',
            'rules' => EnterpriseReleaseVersion::CURRENT,
        ], $dryRun);
    }

    /**
     * @return array{ok: bool, value: string, expected: string}
     */
    public function verifyVersionFile(): array
    {
        $path = base_path('VERSION');
        $raw = is_file($path) ? trim((string) file_get_contents($path)) : '';
        $expected = EnterpriseReleaseVersion::TAG;

        return [
            'ok' => $raw === $expected,
            'value' => $raw,
            'expected' => $expected,
        ];
    }

    /**
     * @return list<array{id: string, ok: bool, detail: string}>
     */
    public function verifyArtifacts(): array
    {
        $root = base_path();
        $checks = [];
        foreach ((array) config('release.artifacts', []) as $rel) {
            if (!is_string($rel) || $rel === '') {
                continue;
            }
            $path = $root . '/' . str_replace('\\', '/', $rel);
            $ok = is_file($path) && filesize($path) > 0;
            $detail = $ok ? 'present' : 'missing_or_empty';
            if ($ok && $rel === 'CHANGELOG.md') {
                $src = (string) file_get_contents($path);
                $ok = str_contains($src, '[1.0.0]') || str_contains($src, '## [1.0.0]');
                $detail = $ok ? 'mentions_1.0.0' : 'missing_1.0.0_section';
            }
            if ($ok && $rel === 'RELEASE_NOTES.md') {
                $src = (string) file_get_contents($path);
                $ok = str_contains($src, '1.0.0') && str_contains($src, 'v1.0.0');
                $detail = $ok ? 'mentions_v1.0.0' : 'missing_version_markers';
            }
            if ($ok && $rel === 'LICENSE') {
                $src = (string) file_get_contents($path);
                $ok = str_contains($src, 'Readleaf') && str_contains(strtoupper($src), 'PROPRIETARY');
                $detail = $ok ? 'proprietary_readleaf' : 'unexpected_license';
            }
            if ($ok && $rel === 'release/manifest.json') {
                $data = json_decode((string) file_get_contents($path), true);
                $ok = is_array($data)
                    && ($data['version'] ?? '') === EnterpriseReleaseVersion::CURRENT
                    && ($data['tag'] ?? '') === EnterpriseReleaseVersion::TAG;
                $detail = $ok ? 'manifest_aligned' : 'manifest_mismatch';
            }
            if ($ok && $rel === 'composer.json') {
                $data = json_decode((string) file_get_contents($path), true);
                $ok = is_array($data) && ($data['version'] ?? '') === EnterpriseReleaseVersion::CURRENT;
                $detail = $ok ? 'composer_1.0.0' : 'composer_version_mismatch';
            }
            $checks[] = ['id' => $rel, 'ok' => $ok, 'detail' => $detail];
        }

        return $checks;
    }

    /**
     * @return list<array{id: string, ok: bool, expected: string, actual: string}>
     */
    public function verifyModules(): array
    {
        $expected = [
            'production' => '4.1.0',
            'performance' => '4.2.0',
            'observability' => '4.3.0',
            'deployment' => '4.4.0',
            'api' => '4.5.0',
            'api_portal' => '4.6.0',
            'security' => '4.7.0',
            'frontend' => '4.8.0',
            'testing_rc' => '4.9.0',
            'enterprise_release' => '1.0.0',
        ];
        $actual = [
            'production' => ProductionReadinessVersion::CURRENT,
            'performance' => PerformanceVersion::CURRENT,
            'observability' => ObservabilityVersion::CURRENT,
            'deployment' => DeploymentVersion::CURRENT,
            'api' => ApiVersion::CURRENT,
            'api_portal' => DeveloperPortalVersion::CURRENT,
            'security' => SecurityHardeningVersion::CURRENT,
            'frontend' => FrontendPolishVersion::CURRENT,
            'testing_rc' => ReleaseCandidateVersion::CURRENT,
            'enterprise_release' => EnterpriseReleaseVersion::CURRENT,
        ];

        $out = [];
        foreach ($expected as $id => $exp) {
            $act = $actual[$id] ?? '';
            $out[] = [
                'id' => $id,
                'ok' => $act === $exp,
                'expected' => $exp,
                'actual' => $act,
            ];
        }

        return $out;
    }

    public function composerAligned(): bool
    {
        $path = base_path('composer.json');
        if (!is_file($path)) {
            return false;
        }
        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) && ($data['version'] ?? null) === EnterpriseReleaseVersion::CURRENT;
    }
}

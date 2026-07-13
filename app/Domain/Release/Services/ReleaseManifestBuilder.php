<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Release\Services;

use JobVisa\App\Domain\Api\Portal\Support\DeveloperPortalVersion;
use JobVisa\App\Domain\Api\Support\ApiVersion;
use JobVisa\App\Domain\Deployment\Support\DeploymentVersion;
use JobVisa\App\Domain\Frontend\Support\FrontendPolishVersion;
use JobVisa\App\Domain\Observability\Support\ObservabilityVersion;
use JobVisa\App\Domain\Performance\Support\PerformanceVersion;
use JobVisa\App\Domain\Production\Support\ProductionReadinessVersion;
use JobVisa\App\Domain\Release\Support\EnterpriseReleaseVersion;
use JobVisa\App\Domain\Security\Support\SecurityHardeningVersion;
use JobVisa\App\Domain\Testing\Support\ReleaseCandidateVersion;

/**
 * Builds and persists the enterprise release manifest.
 */
final class ReleaseManifestBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $root = base_path();
        $artifacts = (array) config('release.artifacts', []);
        $files = [];
        foreach ($artifacts as $rel) {
            if (!is_string($rel) || $rel === '') {
                continue;
            }
            $path = $root . '/' . str_replace('\\', '/', $rel);
            $files[$rel] = [
                'present' => is_file($path),
                'sha256' => is_file($path) ? hash_file('sha256', $path) : null,
                'bytes' => is_file($path) ? filesize($path) : null,
            ];
        }

        return [
            'schema' => 'jobvisa.release.manifest/v1',
            'product' => (string) config('release.product', 'JobVisa.lk'),
            'version' => EnterpriseReleaseVersion::CURRENT,
            'tag' => EnterpriseReleaseVersion::TAG,
            'released_at' => EnterpriseReleaseVersion::RELEASE_DATE,
            'vendor' => (string) config('release.vendor', 'Readleaf (Pvt) Ltd'),
            'license' => (string) config('release.license', 'proprietary'),
            'php_requirement' => '>=8.2',
            'composer_version' => (string) ($this->composerVersion() ?? EnterpriseReleaseVersion::CURRENT),
            'modules' => [
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
            ],
            'gates' => [
                'production-check',
                'performance-check',
                'observability-check',
                'api-check',
                'api-portal-check',
                'security-check',
                'frontend-check',
                'release-candidate-check',
                'enterprise-release-check',
            ],
            'artifacts' => $files,
            'generated_at' => gmdate('c'),
        ];
    }

    /**
     * @return array{ok: bool, path: string, manifest: array<string, mixed>}
     */
    public function write(bool $dryRun = false): array
    {
        $manifest = $this->build();
        $rel = (string) config('release.manifest_path', 'release/manifest.json');
        $path = base_path($rel);
        $dir = dirname($path);
        if (!$dryRun) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            // Write without embedding this file's own hash first, then refresh hash for manifest entry.
            $payload = $manifest;
            unset($payload['artifacts'][$rel]['sha256'], $payload['artifacts'][$rel]['bytes']);
            file_put_contents(
                $path,
                (string) json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n",
                LOCK_EX
            );
            $manifest = $this->build();
            file_put_contents(
                $path,
                (string) json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n",
                LOCK_EX
            );
        }

        return ['ok' => true, 'path' => $path, 'manifest' => $manifest];
    }

    private function composerVersion(): ?string
    {
        $path = base_path('composer.json');
        if (!is_file($path)) {
            return null;
        }
        $data = json_decode((string) file_get_contents($path), true);
        if (!is_array($data)) {
            return null;
        }
        $v = $data['version'] ?? null;

        return is_string($v) ? $v : null;
    }
}

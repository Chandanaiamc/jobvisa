<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Testing\Services;

use JobVisa\App\Domain\Api\Support\ApiVersion;
use JobVisa\App\Domain\Api\Portal\Support\DeveloperPortalVersion;
use JobVisa\App\Domain\Deployment\Support\DeploymentVersion;
use JobVisa\App\Domain\Frontend\Support\FrontendPolishVersion;
use JobVisa\App\Domain\Observability\Support\ObservabilityVersion;
use JobVisa\App\Domain\Performance\Support\PerformanceVersion;
use JobVisa\App\Domain\Production\Support\ProductionReadinessVersion;
use JobVisa\App\Domain\Security\Support\SecurityHardeningVersion;
use JobVisa\App\Domain\Testing\Support\RcChecklist;
use JobVisa\App\Domain\Testing\Support\ReleaseCandidateVersion;

/**
 * Release Candidate readiness + checklist evaluation.
 */
final class ReleaseCandidateService
{
    /**
     * @return array<string, mixed>
     */
    public function status(): array
    {
        $checklist = $this->evaluateChecklist();
        $failed = array_values(array_filter(
            $checklist,
            static fn (array $row): bool => !($row['ok'] ?? false)
        ));

        return [
            'status' => $failed === [] ? 'ok' : 'failed',
            'version' => ReleaseCandidateVersion::CURRENT,
            'enabled' => (bool) config('testing.enabled', true),
            'suites' => (array) config('testing.suites', []),
            'gates' => array_keys((array) config('testing.gates', [])),
            'checklist' => [
                'total' => count($checklist),
                'passed' => count($checklist) - count($failed),
                'failed' => count($failed),
                'items' => $checklist,
            ],
            'compat' => [
                'production' => ProductionReadinessVersion::CURRENT,
                'performance' => PerformanceVersion::CURRENT,
                'observability' => ObservabilityVersion::CURRENT,
                'deployment' => DeploymentVersion::CURRENT,
                'api' => ApiVersion::CURRENT,
                'api_portal' => DeveloperPortalVersion::CURRENT,
                'security' => SecurityHardeningVersion::CURRENT,
                'frontend' => FrontendPolishVersion::CURRENT,
            ],
        ];
    }

    /**
     * @return list<array{id: string, label: string, category: string, ok: bool, detail: string}>
     */
    public function evaluateChecklist(): array
    {
        $root = base_path();
        $providers = is_file($root . '/config/providers.php')
            ? require $root . '/config/providers.php'
            : [];
        $composer = is_file($root . '/composer.json')
            ? (string) file_get_contents($root . '/composer.json')
            : '';
        $ci = is_file($root . '/.github/workflows/ci.yml')
            ? (string) file_get_contents($root . '/.github/workflows/ci.yml')
            : '';
        $phpunit = is_file($root . '/phpunit.xml')
            ? (string) file_get_contents($root . '/phpunit.xml')
            : '';

        $checks = [
            'version_490' => ReleaseCandidateVersion::CURRENT === '4.9.0',
            'docs_rc' => is_file($root . '/docs/02-system-design/enterprise-testing-release-candidate.md'),
            'docs_checklist' => is_file($root . '/docs/07-testing/release-candidate-checklist.md'),
            'phpunit_config' => str_contains($phpunit, 'testsuite name="Unit"')
                && str_contains($phpunit, 'testsuite name="Smoke"'),
            'suite_unit' => is_dir($root . '/tests/Unit'),
            'suite_feature' => is_dir($root . '/tests/Feature'),
            'suite_integration' => is_dir($root . '/tests/Integration'),
            'suite_api' => is_dir($root . '/tests/Api'),
            'suite_security' => is_dir($root . '/tests/Security'),
            'suite_performance' => is_dir($root . '/tests/Performance'),
            'suite_smoke' => is_dir($root . '/tests/Smoke'),
            'gate_production' => is_file($root . '/scripts/production-check.php'),
            'gate_performance' => is_file($root . '/scripts/performance-check.php'),
            'gate_observability' => is_file($root . '/scripts/observability-check.php'),
            'gate_api' => is_file($root . '/scripts/api-check.php'),
            'gate_security' => is_file($root . '/scripts/security-check.php'),
            'gate_frontend' => is_file($root . '/scripts/frontend-check.php'),
            'gate_rc' => is_file($root . '/scripts/release-candidate-check.php'),
            'ci_workflow' => str_contains($ci, 'Domain/Testing') || str_contains($ci, 'release-candidate'),
            'composer_scripts' => str_contains($composer, 'release-candidate-check'),
            'no_breaking' => ProductionReadinessVersion::CURRENT === '4.1.0'
                && PerformanceVersion::CURRENT === '4.2.0'
                && ObservabilityVersion::CURRENT === '4.3.0'
                && DeploymentVersion::CURRENT === '4.4.0'
                && ApiVersion::CURRENT === '4.5.0'
                && DeveloperPortalVersion::CURRENT === '4.6.0'
                && SecurityHardeningVersion::CURRENT === '4.7.0'
                && FrontendPolishVersion::CURRENT === '4.8.0',
            'provider' => in_array(
                \JobVisa\App\Providers\TestingServiceProvider::class,
                $providers,
                true
            ),
        ];

        $out = [];
        foreach (RcChecklist::items() as $item) {
            $id = $item['id'];
            $ok = (bool) ($checks[$id] ?? false);
            $out[] = [
                'id' => $id,
                'label' => $item['label'],
                'category' => $item['category'],
                'ok' => $ok,
                'detail' => $ok ? 'pass' : 'fail',
            ];
        }

        return $out;
    }
}

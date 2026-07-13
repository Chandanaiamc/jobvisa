<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Testing\Services;

use JobVisa\App\Domain\Api\Auth\PersonalAccessTokenService;
use JobVisa\App\Domain\Api\Portal\Services\DeveloperPortalService;
use JobVisa\App\Domain\Deployment\Services\DeploymentManager;
use JobVisa\App\Domain\Frontend\Services\FrontendPolishService;
use JobVisa\App\Domain\Observability\Services\ObservabilityService;
use JobVisa\App\Domain\Performance\Services\PerformanceHealthService;
use JobVisa\App\Domain\Production\Services\ProductionHealthService;
use JobVisa\App\Domain\Security\Services\SecurityHardeningService;
use JobVisa\App\Domain\Testing\Support\ReleaseCandidateVersion;
use Throwable;

/**
 * In-process smoke probes for Release Candidate (no HTTP server required).
 */
final class SmokeTestService
{
    public function __construct(
        private readonly ProductionHealthService $production,
        private readonly PerformanceHealthService $performance,
        private readonly ObservabilityService $observability,
        private readonly SecurityHardeningService $security,
        private readonly FrontendPolishService $frontend,
        private readonly DeploymentManager $deployment,
        private readonly PersonalAccessTokenService $tokens,
        private readonly DeveloperPortalService $portal,
    ) {
    }

    /**
     * @return array{ok: bool, version: string, probes: array<string, array{ok: bool, detail: mixed}>}
     */
    public function run(): array
    {
        $probes = [];

        try {
            $live = $this->production->live();
            $probes['production_live'] = [
                'ok' => ($live['status'] ?? '') === 'ok',
                'detail' => $live['status'] ?? null,
            ];
        } catch (Throwable $e) {
            $probes['production_live'] = ['ok' => false, 'detail' => $e->getMessage()];
        }

        try {
            $ready = $this->production->ready();
            $probes['production_ready'] = [
                'ok' => ($ready['payload']['checks']['database']['ok'] ?? false) === true
                    || ($ready['payload']['status'] ?? '') === 'ok',
                'detail' => $ready['payload']['status'] ?? null,
            ];
        } catch (Throwable $e) {
            $probes['production_ready'] = ['ok' => false, 'detail' => $e->getMessage()];
        }

        $map = [
            'performance' => static fn (self $s) => $s->performance->status(),
            'observability' => static fn (self $s) => $s->observability->status(),
            'security' => static fn (self $s) => $s->security->status(),
            'frontend' => static fn (self $s) => $s->frontend->status(),
            'portal' => static fn (self $s) => $s->portal->status(),
        ];
        foreach ($map as $name => $fn) {
            try {
                $status = $fn($this);
                $probes[$name] = [
                    'ok' => ($status['status'] ?? '') === 'ok',
                    'detail' => $status['version'] ?? ($status['status'] ?? null),
                ];
            } catch (Throwable $e) {
                $probes[$name] = ['ok' => false, 'detail' => $e->getMessage()];
            }
        }

        try {
            $dry = $this->deployment->run(true, null, '4.9.0-smoke');
            $probes['deployment_dry_run'] = [
                'ok' => ($dry['ok'] ?? false) === true,
                'detail' => $dry['message'] ?? null,
            ];
        } catch (Throwable $e) {
            $probes['deployment_dry_run'] = ['ok' => false, 'detail' => $e->getMessage()];
        }

        $probes['pat_service'] = [
            'ok' => $this->tokens instanceof PersonalAccessTokenService,
            'detail' => 'PersonalAccessTokenService',
        ];
        $probes['rc_version'] = [
            'ok' => ReleaseCandidateVersion::CURRENT === '4.9.0',
            'detail' => ReleaseCandidateVersion::CURRENT,
        ];

        $ok = true;
        foreach ($probes as $p) {
            if (!($p['ok'] ?? false)) {
                $ok = false;
                break;
            }
        }

        return [
            'ok' => $ok,
            'version' => ReleaseCandidateVersion::CURRENT,
            'probes' => $probes,
        ];
    }
}

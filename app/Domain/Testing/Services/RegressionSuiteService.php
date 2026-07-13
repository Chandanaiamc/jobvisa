<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Testing\Services;

/**
 * Aggregates regression categories for status / reporting.
 */
final class RegressionSuiteService
{
    public function __construct(
        private readonly ReleaseCandidateService $rc,
        private readonly SmokeTestService $smoke,
        private readonly QaGateRunner $gates,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(bool $dryRun = true): array
    {
        $status = $this->rc->status();
        $smoke = $this->smoke->run();

        return [
            'status' => (($status['status'] ?? '') === 'ok' && ($smoke['ok'] ?? false)) ? 'ok' : 'failed',
            'version' => $status['version'] ?? null,
            'checklist_ok' => ($status['status'] ?? '') === 'ok',
            'smoke_ok' => (bool) ($smoke['ok'] ?? false),
            'smoke' => $smoke,
            'gates_dry' => $this->gates->runEnterpriseGates(null, $dryRun),
            'phpunit_dry' => $this->gates->runPhpUnit($dryRun),
            'categories' => [
                'unit' => 'tests/Unit',
                'feature' => 'tests/Feature',
                'integration' => 'tests/Integration',
                'api' => 'tests/Api',
                'security' => 'tests/Security',
                'performance' => 'tests/Performance',
                'smoke' => 'tests/Smoke',
            ],
        ];
    }
}

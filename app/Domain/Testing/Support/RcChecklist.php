<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Testing\Support;

/**
 * Release Candidate checklist (structural + process gates).
 */
final class RcChecklist
{
    /**
     * @return list<array{id: string, label: string, category: string}>
     */
    public static function items(): array
    {
        return [
            ['id' => 'version_490', 'label' => 'Release Candidate rules version is 4.9.0', 'category' => 'meta'],
            ['id' => 'docs_rc', 'label' => 'RC documentation published', 'category' => 'docs'],
            ['id' => 'docs_checklist', 'label' => 'RC checklist document published', 'category' => 'docs'],
            ['id' => 'phpunit_config', 'label' => 'PHPUnit configured with named suites', 'category' => 'tests'],
            ['id' => 'suite_unit', 'label' => 'Unit regression suite present', 'category' => 'tests'],
            ['id' => 'suite_feature', 'label' => 'Feature / regression suite present', 'category' => 'tests'],
            ['id' => 'suite_integration', 'label' => 'Integration suite present', 'category' => 'tests'],
            ['id' => 'suite_api', 'label' => 'API suite present', 'category' => 'tests'],
            ['id' => 'suite_security', 'label' => 'Security regression suite present', 'category' => 'tests'],
            ['id' => 'suite_performance', 'label' => 'Performance regression suite present', 'category' => 'tests'],
            ['id' => 'suite_smoke', 'label' => 'Smoke suite present', 'category' => 'tests'],
            ['id' => 'gate_production', 'label' => 'Production readiness gate script present', 'category' => 'gates'],
            ['id' => 'gate_performance', 'label' => 'Performance gate script present', 'category' => 'gates'],
            ['id' => 'gate_observability', 'label' => 'Observability gate script present', 'category' => 'gates'],
            ['id' => 'gate_api', 'label' => 'API gate script present', 'category' => 'gates'],
            ['id' => 'gate_security', 'label' => 'Security gate script present', 'category' => 'gates'],
            ['id' => 'gate_frontend', 'label' => 'Frontend a11y gate script present', 'category' => 'gates'],
            ['id' => 'gate_rc', 'label' => 'Release candidate check script present', 'category' => 'gates'],
            ['id' => 'ci_workflow', 'label' => 'CI workflow includes Testing lint / unit gate', 'category' => 'ci'],
            ['id' => 'composer_scripts', 'label' => 'Composer release-candidate-check script wired', 'category' => 'ci'],
            ['id' => 'no_breaking', 'label' => 'Prior module versions intact (4.1–4.8)', 'category' => 'compat'],
            ['id' => 'provider', 'label' => 'TestingServiceProvider registered', 'category' => 'di'],
        ];
    }
}

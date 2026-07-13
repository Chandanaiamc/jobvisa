<?php

declare(strict_types=1);

namespace JobVisa\Tests\Smoke;

use JobVisa\App\Domain\Testing\Services\SmokeTestService;
use JobVisa\App\Domain\Testing\Support\ReleaseCandidateVersion;
use JobVisa\Tests\Support\ApplicationTestCase;

final class EnterpriseSmokeTest extends ApplicationTestCase
{
    public function testReleaseCandidateVersion(): void
    {
        $this->assertSame('4.9.0', ReleaseCandidateVersion::CURRENT);
    }

    public function testInProcessSmokeProbes(): void
    {
        /** @var SmokeTestService $smoke */
        $smoke = $this->container->get(SmokeTestService::class);
        $result = $smoke->run();
        $this->assertTrue($result['ok'], json_encode($result['probes'] ?? []));
    }
}

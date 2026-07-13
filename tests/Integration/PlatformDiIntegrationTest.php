<?php

declare(strict_types=1);

namespace JobVisa\Tests\Integration;

use App\Core\Database;
use JobVisa\App\Auth\AuthManager;
use JobVisa\App\Domain\Api\Auth\PersonalAccessTokenService;
use JobVisa\App\Domain\Api\Portal\Services\DeveloperPortalService;
use JobVisa\App\Domain\Deployment\Services\DeploymentManager;
use JobVisa\App\Domain\Frontend\Services\FrontendPolishService;
use JobVisa\App\Domain\Security\Services\SecurityHardeningService;
use JobVisa\App\Domain\Testing\Services\ReleaseCandidateService;
use JobVisa\App\Domain\Testing\Services\SmokeTestService;
use JobVisa\Tests\Support\ApplicationTestCase;
use Throwable;

final class PlatformDiIntegrationTest extends ApplicationTestCase
{
    public function testCorePlatformServicesResolve(): void
    {
        foreach ([
            AuthManager::class,
            SecurityHardeningService::class,
            FrontendPolishService::class,
            PersonalAccessTokenService::class,
            DeveloperPortalService::class,
            DeploymentManager::class,
            ReleaseCandidateService::class,
            SmokeTestService::class,
        ] as $class) {
            $this->assertInstanceOf($class, $this->container->get($class));
        }
    }

    public function testReleaseCandidateChecklistPassesStructurally(): void
    {
        /** @var ReleaseCandidateService $rc */
        $rc = $this->container->get(ReleaseCandidateService::class);
        $status = $rc->status();
        $this->assertSame('4.9.0', $status['version']);
        $this->assertSame('ok', $status['status'], json_encode($status['checklist'] ?? []));
    }

    public function testDatabaseConnectivityWhenAvailable(): void
    {
        try {
            $col = Database::connection()->query('SELECT 1')->fetchColumn();
            $this->assertSame('1', (string) $col);
        } catch (Throwable $e) {
            $this->markTestSkipped('Database unavailable: ' . $e->getMessage());
        }
    }
}

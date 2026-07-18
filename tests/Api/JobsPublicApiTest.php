<?php

declare(strict_types=1);

namespace JobVisa\Tests\Api;

use App\Core\Database;
use JobVisa\App\Domain\Job\Services\PublicJobsService;
use JobVisa\Tests\Support\ApplicationTestCase;
use PDO;
use Throwable;

final class JobsPublicApiTest extends ApplicationTestCase
{
    private function hasPublishedJob(): bool
    {
        try {
            $row = Database::query(
                "SELECT id FROM jobs WHERE status = 'published' ORDER BY id ASC LIMIT 1"
            )->fetch(PDO::FETCH_ASSOC);

            return is_array($row) && (int) ($row['id'] ?? 0) > 0;
        } catch (Throwable) {
            return false;
        }
    }

    public function testSearchPublishedSupportsPaginationAndFilters(): void
    {
        if (!$this->hasPublishedJob()) {
            $this->markTestSkipped('No published jobs in database');
        }

        /** @var PublicJobsService $svc */
        $svc = $this->container->get(PublicJobsService::class);
        $page1 = $svc->search(['page' => 1, 'per_page' => 5]);
        $this->assertArrayHasKey('jobs', $page1);
        $this->assertArrayHasKey('pagination', $page1);
        $this->assertSame(1, $page1['pagination']['page']);
        $this->assertSame(5, $page1['pagination']['per_page']);
        $this->assertGreaterThanOrEqual(1, $page1['pagination']['total']);
        $this->assertNotEmpty($page1['jobs']);
        $this->assertArrayHasKey('summary', $page1['jobs'][0]);
        $this->assertArrayNotHasKey('description', $page1['jobs'][0]);

        $options = $svc->filterOptions();
        $this->assertArrayHasKey('countries', $options);
        $this->assertArrayHasKey('job_types', $options);
    }

    public function testFindPublishedDetailIncludesDescription(): void
    {
        if (!$this->hasPublishedJob()) {
            $this->markTestSkipped('No published jobs in database');
        }

        $id = (int) Database::query(
            "SELECT id FROM jobs WHERE status = 'published' ORDER BY id ASC LIMIT 1"
        )->fetchColumn();

        /** @var PublicJobsService $svc */
        $svc = $this->container->get(PublicJobsService::class);
        $job = $svc->find($id);
        $this->assertNotNull($job);
        $this->assertSame($id, (int) $job['id']);
        $this->assertArrayHasKey('description', $job);
        $this->assertArrayHasKey('summary', $job);
    }

    public function testLegacyLimitStillWorksViaNormalize(): void
    {
        /** @var PublicJobsService $svc */
        $svc = $this->container->get(PublicJobsService::class);
        $filters = $svc->normalizeFilters(['limit' => 7]);
        $this->assertSame(7, $filters['per_page']);
        $this->assertSame(1, $filters['page']);
    }

    public function testSearchRejectsUnknownJobGracefully(): void
    {
        /** @var PublicJobsService $svc */
        $svc = $this->container->get(PublicJobsService::class);
        $this->assertNull($svc->find(999999999));
    }

    public function testKeywordSearchDoesNotThrow(): void
    {
        if (!$this->hasPublishedJob()) {
            $this->markTestSkipped('No published jobs in database');
        }
        /** @var PublicJobsService $svc */
        $svc = $this->container->get(PublicJobsService::class);
        $result = $svc->search(['q' => 'a', 'page' => 1, 'per_page' => 10]);
        $this->assertArrayHasKey('jobs', $result);
        $result2 = $svc->search(['q' => 'engineer', 'page' => 1, 'per_page' => 10]);
        $this->assertArrayHasKey('pagination', $result2);
    }
}

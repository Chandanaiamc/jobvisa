<?php

declare(strict_types=1);

namespace JobVisa\Tests\Api;

use App\Core\Database;
use JobVisa\App\Domain\Job\Services\EmployerJobsService;
use JobVisa\App\Domain\Job\Services\PublicJobsService;
use JobVisa\App\Repositories\Contracts\JobRepositoryInterface;
use JobVisa\Tests\Support\ApplicationTestCase;
use PDO;
use Throwable;

final class EmployerJobsCrudApiTest extends ApplicationTestCase
{
    /**
     * @return array{id: int, email: string}|null
     */
    private function employerUser(): ?array
    {
        try {
            $row = Database::query(
                "SELECT u.id, u.email
                 FROM users u
                 INNER JOIN employers e ON e.user_id = u.id
                 WHERE u.role = 'employer' AND u.status = 'active'
                 ORDER BY u.id ASC LIMIT 1"
            )->fetch(PDO::FETCH_ASSOC);

            return is_array($row) ? ['id' => (int) $row['id'], 'email' => (string) $row['email']] : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array{id: int}|null
     */
    private function otherEmployerUser(int $excludeUserId): ?array
    {
        try {
            $row = Database::query(
                "SELECT u.id
                 FROM users u
                 INNER JOIN employers e ON e.user_id = u.id
                 WHERE u.role = 'employer' AND u.status = 'active' AND u.id <> ?
                 ORDER BY u.id ASC LIMIT 1",
                [$excludeUserId]
            )->fetch(PDO::FETCH_ASSOC);

            return is_array($row) ? ['id' => (int) $row['id']] : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array{category_id: int, job_type_id: int, country_id: int}|null
     */
    private function taxonomy(): ?array
    {
        try {
            $categoryId = (int) Database::query(
                'SELECT id FROM job_categories WHERE is_active = 1 ORDER BY id ASC LIMIT 1'
            )->fetchColumn();
            $jobTypeId = (int) Database::query(
                'SELECT id FROM job_types WHERE is_active = 1 ORDER BY id ASC LIMIT 1'
            )->fetchColumn();
            $countryId = (int) Database::query(
                'SELECT id FROM countries ORDER BY id ASC LIMIT 1'
            )->fetchColumn();
            if ($categoryId < 1 || $jobTypeId < 1 || $countryId < 1) {
                return null;
            }

            return [
                'category_id' => $categoryId,
                'job_type_id' => $jobTypeId,
                'country_id' => $countryId,
            ];
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function actor(int $userId): array
    {
        return ['id' => $userId, 'role' => 'employer'];
    }

    public function testCreatePublishUnpublishArchiveLifecycle(): void
    {
        $employer = $this->employerUser();
        $tax = $this->taxonomy();
        if ($employer === null || $tax === null) {
            $this->markTestSkipped('Employer profile or taxonomy seed data missing');
        }

        /** @var EmployerJobsService $svc */
        $svc = $this->container->get(EmployerJobsService::class);
        $suffix = bin2hex(random_bytes(4));
        $created = $svc->create($this->actor($employer['id']), [
            'title' => 'CRUD Test Nurse ' . $suffix,
            'description' => 'Phase 1 employer CRUD test listing.',
            'category_id' => $tax['category_id'],
            'job_type_id' => $tax['job_type_id'],
            'country_id' => $tax['country_id'],
            'visa_sponsorship' => true,
            'status' => 'draft',
        ]);
        $this->assertTrue($created['success']);
        $this->assertSame('draft', $created['job']['status'] ?? null);
        $jobId = (int) ($created['job']['id'] ?? 0);
        $this->assertGreaterThan(0, $jobId);

        $published = $svc->publish($this->actor($employer['id']), $jobId);
        $this->assertTrue($published['success']);
        $this->assertSame('published', $published['job']['status'] ?? null);

        /** @var PublicJobsService $public */
        $public = $this->container->get(PublicJobsService::class);
        $this->assertNotNull($public->find($jobId));

        $updated = $svc->update($this->actor($employer['id']), $jobId, [
            'title' => 'CRUD Test Nurse Updated ' . $suffix,
            'description' => 'Updated description for CRUD test.',
        ]);
        $this->assertTrue($updated['success']);
        $this->assertStringContainsString('Updated', (string) ($updated['job']['title'] ?? ''));

        $unpublished = $svc->unpublish($this->actor($employer['id']), $jobId);
        $this->assertTrue($unpublished['success']);
        $this->assertSame('draft', $unpublished['job']['status'] ?? null);
        $this->assertNull($public->find($jobId));

        $archived = $svc->archive($this->actor($employer['id']), $jobId);
        $this->assertTrue($archived['success']);
        $this->assertSame('closed', $archived['job']['status'] ?? null);

        // Soft archive only — row still exists and owned.
        /** @var JobRepositoryInterface $jobs */
        $jobs = $this->container->get(JobRepositoryInterface::class);
        $owned = $jobs->findOwnedByEmployerUser($jobId, $employer['id']);
        $this->assertNotNull($owned);
        $this->assertSame('closed', $owned['status'] ?? null);
    }

    public function testCreatePublishedAppearsInPublicList(): void
    {
        $employer = $this->employerUser();
        $tax = $this->taxonomy();
        if ($employer === null || $tax === null) {
            $this->markTestSkipped('Employer profile or taxonomy seed data missing');
        }

        /** @var EmployerJobsService $svc */
        $svc = $this->container->get(EmployerJobsService::class);
        $suffix = bin2hex(random_bytes(4));
        $created = $svc->create($this->actor($employer['id']), [
            'title' => 'Public Visible Role ' . $suffix,
            'description' => 'Should appear on public jobs API when published.',
            'category_id' => $tax['category_id'],
            'job_type_id' => $tax['job_type_id'],
            'country_id' => $tax['country_id'],
            'status' => 'published',
        ]);
        $this->assertTrue($created['success']);
        $jobId = (int) ($created['job']['id'] ?? 0);

        /** @var PublicJobsService $public */
        $public = $this->container->get(PublicJobsService::class);
        $found = $public->find($jobId);
        $this->assertNotNull($found);
        $this->assertArrayNotHasKey('employer_id', $found);
    }

    public function testOtherEmployerCannotManageJob(): void
    {
        $employer = $this->employerUser();
        $tax = $this->taxonomy();
        if ($employer === null || $tax === null) {
            $this->markTestSkipped('Employer profile or taxonomy seed data missing');
        }
        $other = $this->otherEmployerUser($employer['id']);
        if ($other === null) {
            $this->markTestSkipped('Need a second employer for IDOR check');
        }

        /** @var EmployerJobsService $svc */
        $svc = $this->container->get(EmployerJobsService::class);
        $created = $svc->create($this->actor($employer['id']), [
            'title' => 'Owned Only ' . bin2hex(random_bytes(3)),
            'description' => 'Ownership isolation test.',
            'category_id' => $tax['category_id'],
            'job_type_id' => $tax['job_type_id'],
            'country_id' => $tax['country_id'],
            'status' => 'draft',
        ]);
        $jobId = (int) ($created['job']['id'] ?? 0);
        $this->assertGreaterThan(0, $jobId);

        $blocked = $svc->update($this->actor($other['id']), $jobId, ['title' => 'Hijack']);
        $this->assertFalse($blocked['success']);
        $this->assertStringContainsString('not found', strtolower((string) ($blocked['message'] ?? '')));

        $blockedPublish = $svc->publish($this->actor($other['id']), $jobId);
        $this->assertFalse($blockedPublish['success']);
    }

    public function testSeekerActorCannotCreate(): void
    {
        $tax = $this->taxonomy();
        if ($tax === null) {
            $this->markTestSkipped('Taxonomy seed data missing');
        }
        /** @var EmployerJobsService $svc */
        $svc = $this->container->get(EmployerJobsService::class);
        $result = $svc->create(['id' => 1, 'role' => 'seeker'], [
            'title' => 'Should Fail',
            'description' => 'Seekers cannot create jobs.',
            'category_id' => $tax['category_id'],
            'job_type_id' => $tax['job_type_id'],
            'country_id' => $tax['country_id'],
        ]);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not allowed', strtolower((string) ($result['message'] ?? '')));
    }

    public function testValidationRejectsEmptyTitle(): void
    {
        $employer = $this->employerUser();
        $tax = $this->taxonomy();
        if ($employer === null || $tax === null) {
            $this->markTestSkipped('Employer profile or taxonomy seed data missing');
        }
        /** @var EmployerJobsService $svc */
        $svc = $this->container->get(EmployerJobsService::class);
        $result = $svc->create($this->actor($employer['id']), [
            'title' => '',
            'description' => 'Missing title.',
            'category_id' => $tax['category_id'],
            'job_type_id' => $tax['job_type_id'],
            'country_id' => $tax['country_id'],
        ]);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('errors', $result);
    }
}

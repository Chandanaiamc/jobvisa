<?php

declare(strict_types=1);

namespace JobVisa\Tests\Api;

use App\Core\Database;
use JobVisa\App\Domain\Application\Services\ApplicationService;
use JobVisa\App\Domain\Job\Services\EmployerJobsService;
use JobVisa\App\Repositories\Contracts\JobRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeRepositoryInterface;
use JobVisa\Tests\Support\ApplicationTestCase;
use PDO;
use Throwable;

final class ApplicationsApiTest extends ApplicationTestCase
{
    /**
     * @return array{id: int}|null
     */
    private function seeker(): ?array
    {
        try {
            $row = Database::query(
                "SELECT id FROM users WHERE role = 'seeker' AND status = 'active' ORDER BY id ASC LIMIT 1"
            )->fetch(PDO::FETCH_ASSOC);

            return is_array($row) ? ['id' => (int) $row['id']] : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array{id: int}|null
     */
    private function employer(): ?array
    {
        try {
            $row = Database::query(
                "SELECT u.id
                 FROM users u
                 INNER JOIN employers e ON e.user_id = u.id
                 WHERE u.role = 'employer' AND u.status = 'active'
                 ORDER BY u.id ASC LIMIT 1"
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

    private function historyReady(): bool
    {
        try {
            Database::query('SELECT 1 FROM application_status_history LIMIT 1');

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function testApplyDuplicateWithdrawReopenAndEmployerStatus(): void
    {
        if (!$this->historyReady()) {
            $this->markTestSkipped('Migration 067 application_status_history not applied');
        }
        $seeker = $this->seeker();
        $employer = $this->employer();
        $tax = $this->taxonomy();
        if ($seeker === null || $employer === null || $tax === null) {
            $this->markTestSkipped('Seed seeker/employer/taxonomy required');
        }

        /** @var ResumeRepositoryInterface $resumes */
        $resumes = $this->container->get(ResumeRepositoryInterface::class);
        $resume = $resumes->ensurePrimary($seeker['id'], 'Applications Test CV');

        /** @var EmployerJobsService $jobsSvc */
        $jobsSvc = $this->container->get(EmployerJobsService::class);
        $suffix = bin2hex(random_bytes(3));
        $createdJob = $jobsSvc->create(
            ['id' => $employer['id'], 'role' => 'employer'],
            [
                'title' => 'Apply Target ' . $suffix,
                'description' => 'Published job for applications tests.',
                'category_id' => $tax['category_id'],
                'job_type_id' => $tax['job_type_id'],
                'country_id' => $tax['country_id'],
                'status' => 'published',
            ]
        );
        $this->assertTrue($createdJob['success']);
        $jobId = (int) ($createdJob['job']['id'] ?? 0);

        /** @var ApplicationService $apps */
        $apps = $this->container->get(ApplicationService::class);
        $seekerActor = ['id' => $seeker['id'], 'role' => 'seeker'];
        $employerActor = ['id' => $employer['id'], 'role' => 'employer'];

        $applied = $apps->apply($seekerActor, $jobId, [
            'resume_id' => (int) $resume['id'],
            'cover_letter' => 'Optional cover letter',
        ]);
        $this->assertTrue($applied['success']);
        $this->assertSame('submitted', $applied['application']['status'] ?? null);
        $appId = (int) ($applied['application']['id'] ?? 0);

        $dup = $apps->apply($seekerActor, $jobId, ['resume_id' => (int) $resume['id']]);
        $this->assertFalse($dup['success']);
        $this->assertTrue($dup['conflict'] ?? false);

        $list = $apps->listForSeeker($seekerActor);
        $this->assertNotEmpty($list);

        $reviewing = $apps->updateStatus($employerActor, $appId, ['status' => 'reviewing']);
        $this->assertTrue($reviewing['success']);
        $this->assertSame('reviewing', $reviewing['application']['status'] ?? null);

        $withdrawn = $apps->withdraw($seekerActor, $appId);
        $this->assertTrue($withdrawn['success']);
        $this->assertSame('withdrawn', $withdrawn['application']['status'] ?? null);

        $cannotWithdrawHiredPath = $apps->withdraw($seekerActor, $appId);
        $this->assertFalse($cannotWithdrawHiredPath['success']);

        $reopen = $apps->apply($seekerActor, $jobId, ['resume_id' => (int) $resume['id']]);
        $this->assertTrue($reopen['success']);
        $this->assertSame('submitted', $reopen['application']['status'] ?? null);

        $rejected = $apps->updateStatus($employerActor, $appId, ['status' => 'rejected']);
        $this->assertTrue($rejected['success']);
        $reopened = $apps->updateStatus($employerActor, $appId, ['status' => 'shortlisted']);
        $this->assertTrue($reopened['success']);
        $this->assertSame('shortlisted', $reopened['application']['status'] ?? null);

        $employerDetail = $apps->getForEmployer($employerActor, $appId);
        $this->assertArrayNotHasKey('email', $employerDetail);
        $this->assertSame($appId, (int) ($employerDetail['id'] ?? 0));
    }

    public function testCannotApplyToDraftOrClosedJob(): void
    {
        if (!$this->historyReady()) {
            $this->markTestSkipped('Migration 067 application_status_history not applied');
        }
        $seeker = $this->seeker();
        $employer = $this->employer();
        $tax = $this->taxonomy();
        if ($seeker === null || $employer === null || $tax === null) {
            $this->markTestSkipped('Seed data missing');
        }

        /** @var ResumeRepositoryInterface $resumes */
        $resumes = $this->container->get(ResumeRepositoryInterface::class);
        $resume = $resumes->ensurePrimary($seeker['id']);

        /** @var EmployerJobsService $jobsSvc */
        $jobsSvc = $this->container->get(EmployerJobsService::class);
        $employerActor = ['id' => $employer['id'], 'role' => 'employer'];
        $draft = $jobsSvc->create($employerActor, [
            'title' => 'Draft Only ' . bin2hex(random_bytes(2)),
            'description' => 'Not open for applications.',
            'category_id' => $tax['category_id'],
            'job_type_id' => $tax['job_type_id'],
            'country_id' => $tax['country_id'],
            'status' => 'draft',
        ]);
        $draftId = (int) ($draft['job']['id'] ?? 0);

        /** @var ApplicationService $apps */
        $apps = $this->container->get(ApplicationService::class);
        $seekerActor = ['id' => $seeker['id'], 'role' => 'seeker'];
        $failDraft = $apps->apply($seekerActor, $draftId, ['resume_id' => (int) $resume['id']]);
        $this->assertFalse($failDraft['success']);
        $this->assertStringContainsString('published', strtolower((string) ($failDraft['message'] ?? '')));

        $published = $jobsSvc->create($employerActor, [
            'title' => 'Then Close ' . bin2hex(random_bytes(2)),
            'description' => 'Will be archived.',
            'category_id' => $tax['category_id'],
            'job_type_id' => $tax['job_type_id'],
            'country_id' => $tax['country_id'],
            'status' => 'published',
        ]);
        $pubId = (int) ($published['job']['id'] ?? 0);
        $jobsSvc->archive($employerActor, $pubId);
        $failClosed = $apps->apply($seekerActor, $pubId, ['resume_id' => (int) $resume['id']]);
        $this->assertFalse($failClosed['success']);
    }

    public function testOwnershipIsolation(): void
    {
        if (!$this->historyReady()) {
            $this->markTestSkipped('Migration 067 application_status_history not applied');
        }
        $seeker = $this->seeker();
        $employer = $this->employer();
        $tax = $this->taxonomy();
        if ($seeker === null || $employer === null || $tax === null) {
            $this->markTestSkipped('Seed data missing');
        }

        /** @var JobRepositoryInterface $jobs */
        $jobs = $this->container->get(JobRepositoryInterface::class);
        $job = $jobs->findPublishedRecordById(
            (int) Database::query("SELECT id FROM jobs WHERE status = 'published' ORDER BY id ASC LIMIT 1")->fetchColumn()
        );
        if ($job === null) {
            $this->markTestSkipped('No published job');
        }

        /** @var ApplicationService $apps */
        $apps = $this->container->get(ApplicationService::class);
        $otherSeekerFail = $apps->apply(
            ['id' => $seeker['id'], 'role' => 'employer'],
            (int) $job['id'],
            []
        );
        $this->assertFalse($otherSeekerFail['success']);

        $employerCannotApply = $apps->apply(
            ['id' => $employer['id'], 'role' => 'employer'],
            (int) $job['id'],
            []
        );
        $this->assertFalse($employerCannotApply['success']);
    }

    public function testInvalidEmployerTransitionFromHired(): void
    {
        if (!$this->historyReady()) {
            $this->markTestSkipped('Migration 067 not applied');
        }
        $seeker = $this->seeker();
        $employer = $this->employer();
        $tax = $this->taxonomy();
        if ($seeker === null || $employer === null || $tax === null) {
            $this->markTestSkipped('Seed data missing');
        }

        $resumes = $this->container->get(ResumeRepositoryInterface::class);
        $resume = $resumes->ensurePrimary($seeker['id']);
        $jobsSvc = $this->container->get(EmployerJobsService::class);
        $employerActor = ['id' => $employer['id'], 'role' => 'employer'];
        $job = $jobsSvc->create($employerActor, [
            'title' => 'Hire Flow ' . bin2hex(random_bytes(2)),
            'description' => 'Status matrix test.',
            'category_id' => $tax['category_id'],
            'job_type_id' => $tax['job_type_id'],
            'country_id' => $tax['country_id'],
            'status' => 'published',
        ]);
        $jobId = (int) ($job['job']['id'] ?? 0);
        $apps = $this->container->get(ApplicationService::class);
        $seekerActor = ['id' => $seeker['id'], 'role' => 'seeker'];
        $applied = $apps->apply($seekerActor, $jobId, ['resume_id' => (int) $resume['id']]);
        $appId = (int) ($applied['application']['id'] ?? 0);
        $apps->updateStatus($employerActor, $appId, ['status' => 'reviewing']);
        $apps->updateStatus($employerActor, $appId, ['status' => 'hired']);
        $bad = $apps->updateStatus($employerActor, $appId, ['status' => 'rejected']);
        $this->assertFalse($bad['success']);

        $cannotWithdraw = $apps->withdraw($seekerActor, $appId);
        $this->assertFalse($cannotWithdraw['success']);
    }
}

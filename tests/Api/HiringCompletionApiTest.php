<?php

declare(strict_types=1);

namespace JobVisa\Tests\Api;

use App\Core\Database;
use JobVisa\App\Domain\Application\Services\ApplicationService;
use JobVisa\App\Domain\HiringCompletion\Services\HiringCompletionService;
use JobVisa\App\Domain\InterviewScheduling\Services\InterviewSchedulingService;
use JobVisa\App\Domain\Job\Services\EmployerJobsService;
use JobVisa\App\Domain\JobOffer\Services\JobOfferService;
use JobVisa\App\Repositories\Contracts\ResumeRepositoryInterface;
use JobVisa\Tests\Support\ApplicationTestCase;
use PDO;
use Throwable;

final class HiringCompletionApiTest extends ApplicationTestCase
{
    private function schemaReady(): bool
    {
        try {
            Database::query('SELECT 1 FROM hire_completions LIMIT 1');
            Database::query('SELECT 1 FROM hire_completion_history LIMIT 1');
            Database::query('SELECT 1 FROM job_offers LIMIT 1');

            return true;
        } catch (Throwable) {
            return false;
        }
    }

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
                "SELECT u.id FROM users u
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

    /**
     * @return array{application_id: int, job_id: int, seeker: array{id: int}, employer: array{id: int}}
     */
    private function shortlistedFixture(int $vacancies = 1): array
    {
        $seeker = $this->seeker();
        $employer = $this->employer();
        $tax = $this->taxonomy();
        $this->assertNotNull($seeker);
        $this->assertNotNull($employer);
        $this->assertNotNull($tax);

        $resumes = $this->container->get(ResumeRepositoryInterface::class);
        $resume = $resumes->ensurePrimary($seeker['id']);

        $jobsSvc = $this->container->get(EmployerJobsService::class);
        $employerActor = ['id' => $employer['id'], 'role' => 'employer'];
        $job = $jobsSvc->create($employerActor, [
            'title' => 'Hire Job ' . bin2hex(random_bytes(3)),
            'description' => 'For hiring completion tests.',
            'category_id' => $tax['category_id'],
            'job_type_id' => $tax['job_type_id'],
            'country_id' => $tax['country_id'],
            'vacancies' => $vacancies,
            'status' => 'published',
        ]);
        $jobId = (int) ($job['job']['id'] ?? 0);

        $apps = $this->container->get(ApplicationService::class);
        $seekerActor = ['id' => $seeker['id'], 'role' => 'seeker'];
        $applied = $apps->apply($seekerActor, $jobId, ['resume_id' => (int) $resume['id']]);
        $appId = (int) ($applied['application']['id'] ?? 0);
        $apps->updateStatus($employerActor, $appId, ['status' => 'shortlisted']);

        return [
            'application_id' => $appId,
            'job_id' => $jobId,
            'seeker' => $seeker,
            'employer' => $employer,
        ];
    }

    public function testOfferAcceptCreatesPendingThenConfirmCompleteClosesJob(): void
    {
        if (!$this->schemaReady()) {
            $this->markTestSkipped('Migration 070 not applied');
        }

        $fx = $this->shortlistedFixture(1);
        $employerActor = ['id' => $fx['employer']['id'], 'role' => 'employer'];
        $seekerActor = ['id' => $fx['seeker']['id'], 'role' => 'seeker'];

        $offers = $this->container->get(JobOfferService::class);
        $created = $offers->create($employerActor, $fx['application_id'], [
            'salary_amount' => 200000,
            'salary_currency' => 'LKR',
            'start_date' => '2026-10-01',
            'expires_at' => '2099-12-31 12:00:00',
            'expires_at_is_utc' => true,
        ]);
        $this->assertTrue($created['success']);
        $offerId = (int) ($created['offer']['id'] ?? 0);
        $offers->send($employerActor, $offerId);

        // Open interview should be cancelled on hire complete
        $interviews = $this->container->get(InterviewSchedulingService::class);
        $scheduled = $interviews->schedule($employerActor, $fx['application_id'], [
            'scheduled_at' => date('Y-m-d H:i:s', strtotime('+3 days')),
            'timezone' => 'UTC',
            'scheduled_at_is_utc' => true,
        ]);
        $this->assertTrue($scheduled['success']);
        $interviewId = (int) ($scheduled['interview']['id'] ?? 0);

        $accepted = $offers->accept($seekerActor, $offerId);
        $this->assertTrue($accepted['success']);
        $this->assertSame('hired', $accepted['offer']['application_status'] ?? null);

        $hires = $this->container->get(HiringCompletionService::class);
        $list = $hires->listForEmployer($employerActor);
        $this->assertNotEmpty($list);
        $hireId = (int) ($list[0]['id'] ?? 0);
        $this->assertSame('pending', $list[0]['status'] ?? null);
        $this->assertSame($offerId, (int) ($list[0]['offer_id'] ?? 0));

        $confirmed = $hires->confirm($employerActor, $hireId, [
            'start_date' => '2026-10-15',
            'notes' => 'Confirmed',
        ]);
        $this->assertTrue($confirmed['success']);
        $this->assertSame('confirmed', $confirmed['hire_completion']['status'] ?? null);

        $completed = $hires->complete($employerActor, $hireId);
        $this->assertTrue($completed['success']);
        $this->assertSame('completed', $completed['hire_completion']['status'] ?? null);
        $this->assertTrue($completed['job_closed'] ?? false);

        $interview = $interviews->getForActor($employerActor, $interviewId);
        $this->assertSame('cancelled', $interview['status'] ?? null);

        $jobRow = Database::query(
            'SELECT status FROM jobs WHERE id = :id',
            ['id' => $fx['job_id']]
        )->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('closed', $jobRow['status'] ?? null);
    }

    public function testEmployerDirectHireCreatesPendingAndCancel(): void
    {
        if (!$this->schemaReady()) {
            $this->markTestSkipped('Migration 070 not applied');
        }

        $fx = $this->shortlistedFixture(2);
        $employerActor = ['id' => $fx['employer']['id'], 'role' => 'employer'];
        $apps = $this->container->get(ApplicationService::class);
        $updated = $apps->updateStatus($employerActor, $fx['application_id'], ['status' => 'hired']);
        $this->assertTrue($updated['success']);

        $hires = $this->container->get(HiringCompletionService::class);
        $list = $hires->listForEmployer($employerActor);
        $match = null;
        foreach ($list as $row) {
            if ((int) ($row['application_id'] ?? 0) === $fx['application_id']) {
                $match = $row;
                break;
            }
        }
        $this->assertNotNull($match);
        $this->assertSame('pending', $match['status'] ?? null);
        $this->assertNull($match['offer_id'] ?? null);

        $cancelled = $hires->cancel($employerActor, (int) $match['id'], 'Mistake');
        $this->assertTrue($cancelled['success']);
        $this->assertSame('cancelled', $cancelled['hire_completion']['status'] ?? null);
    }

    public function testBlockAcceptWhenApplicationRejected(): void
    {
        if (!$this->schemaReady()) {
            $this->markTestSkipped('Migration 070 not applied');
        }

        $fx = $this->shortlistedFixture(1);
        $employerActor = ['id' => $fx['employer']['id'], 'role' => 'employer'];
        $seekerActor = ['id' => $fx['seeker']['id'], 'role' => 'seeker'];

        $offers = $this->container->get(JobOfferService::class);
        $created = $offers->create($employerActor, $fx['application_id'], [
            'salary_amount' => 100000,
            'salary_currency' => 'LKR',
            'expires_at' => '2099-01-01 00:00:00',
            'expires_at_is_utc' => true,
        ]);
        $offerId = (int) ($created['offer']['id'] ?? 0);
        $offers->send($employerActor, $offerId);

        $apps = $this->container->get(ApplicationService::class);
        $apps->updateStatus($employerActor, $fx['application_id'], ['status' => 'rejected']);

        $accepted = $offers->accept($seekerActor, $offerId);
        $this->assertFalse($accepted['success']);
        $this->assertStringContainsString('rejected', strtolower((string) ($accepted['message'] ?? '')));
    }

    public function testOwnershipIsolation(): void
    {
        if (!$this->schemaReady()) {
            $this->markTestSkipped('Migration 070 not applied');
        }

        $fx = $this->shortlistedFixture(1);
        $employerActor = ['id' => $fx['employer']['id'], 'role' => 'employer'];
        $apps = $this->container->get(ApplicationService::class);
        $apps->updateStatus($employerActor, $fx['application_id'], ['status' => 'hired']);

        $hires = $this->container->get(HiringCompletionService::class);
        $list = $hires->listForEmployer($employerActor);
        $hireId = 0;
        foreach ($list as $row) {
            if ((int) ($row['application_id'] ?? 0) === $fx['application_id']) {
                $hireId = (int) $row['id'];
                break;
            }
        }
        $this->assertGreaterThan(0, $hireId);

        $other = $hires->confirm(['id' => $fx['employer']['id'] + 99999, 'role' => 'employer'], $hireId);
        $this->assertFalse($other['success']);

        $seekerCannotConfirm = $hires->confirm(
            ['id' => $fx['seeker']['id'], 'role' => 'seeker'],
            $hireId
        );
        $this->assertFalse($seekerCannotConfirm['success']);
    }
}

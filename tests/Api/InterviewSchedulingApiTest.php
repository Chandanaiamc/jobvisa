<?php

declare(strict_types=1);

namespace JobVisa\Tests\Api;

use App\Core\Database;
use DateTimeImmutable;
use DateTimeZone;
use JobVisa\App\Domain\Application\Services\ApplicationService;
use JobVisa\App\Domain\InterviewScheduling\Services\InterviewSchedulingService;
use JobVisa\App\Domain\InterviewScheduling\Validators\InterviewSchedulingValidator;
use JobVisa\App\Domain\Job\Services\EmployerJobsService;
use JobVisa\App\Repositories\Contracts\ResumeRepositoryInterface;
use JobVisa\Tests\Support\ApplicationTestCase;
use PDO;
use Throwable;

final class InterviewSchedulingApiTest extends ApplicationTestCase
{
    private function schemaReady(): bool
    {
        try {
            Database::query('SELECT 1 FROM scheduled_interviews LIMIT 1');
            Database::query('SELECT 1 FROM scheduled_interview_history LIMIT 1');

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

    private function futureLocal(string $timezone = 'Asia/Colombo'): string
    {
        $dt = new DateTimeImmutable('+2 days 10:00:00', new DateTimeZone($timezone));

        return $dt->format('Y-m-d H:i:s');
    }

    /**
     * @return array{application_id: int, job_id: int, seeker: array{id: int}, employer: array{id: int}}
     */
    private function shortlistedFixture(): array
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
            'title' => 'Interview Job ' . bin2hex(random_bytes(3)),
            'description' => 'For interview scheduling tests.',
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
        $apps->updateStatus($employerActor, $appId, ['status' => 'shortlisted']);

        return [
            'application_id' => $appId,
            'job_id' => $jobId,
            'seeker' => $seeker,
            'employer' => $employer,
        ];
    }

    public function testUtcTimezoneConversion(): void
    {
        $v = new InterviewSchedulingValidator();
        $utc = $v->parseToUtc('2026-08-01 10:00:00', 'Asia/Colombo', false);
        $this->assertNotNull($utc);
        $this->assertSame('UTC', $utc->getTimezone()->getName());
        // Colombo is UTC+5:30 → 10:00 local = 04:30 UTC
        $this->assertSame('04:30', $utc->format('H:i'));
        $local = $v->formatLocal($utc, 'Asia/Colombo');
        $this->assertStringContainsString('10:00:00', $local);
    }

    public function testScheduleConfirmRescheduleCompleteLifecycle(): void
    {
        if (!$this->schemaReady()) {
            $this->markTestSkipped('Migration 068 not applied');
        }

        $fx = $this->shortlistedFixture();
        $svc = $this->container->get(InterviewSchedulingService::class);
        $employerActor = ['id' => $fx['employer']['id'], 'role' => 'employer'];
        $seekerActor = ['id' => $fx['seeker']['id'], 'role' => 'seeker'];

        $created = $svc->schedule($employerActor, $fx['application_id'], [
            'scheduled_at' => $this->futureLocal(),
            'timezone' => 'Asia/Colombo',
            'duration_minutes' => 45,
            'location_type' => 'phone',
        ]);
        $this->assertTrue($created['success']);
        $this->assertSame('proposed', $created['interview']['status'] ?? null);
        $this->assertNotEmpty($created['interview']['scheduled_at_utc'] ?? null);
        $this->assertSame('Asia/Colombo', $created['interview']['timezone'] ?? null);
        $interviewId = (int) ($created['interview']['id'] ?? 0);

        $dup = $svc->schedule($employerActor, $fx['application_id'], [
            'scheduled_at' => $this->futureLocal(),
            'timezone' => 'Asia/Colombo',
        ]);
        $this->assertFalse($dup['success']);
        $this->assertTrue($dup['conflict'] ?? false);

        $confirmed = $svc->confirm($seekerActor, $interviewId);
        $this->assertTrue($confirmed['success']);
        $this->assertSame('confirmed', $confirmed['interview']['status'] ?? null);

        $rescheduled = $svc->reschedule($employerActor, $interviewId, [
            'scheduled_at' => $this->futureLocal(),
            'timezone' => 'Asia/Colombo',
            'duration_minutes' => 60,
        ]);
        $this->assertTrue($rescheduled['success']);
        $this->assertSame('proposed', $rescheduled['interview']['status'] ?? null);
        $this->assertGreaterThan(1, (int) ($rescheduled['interview']['round_number'] ?? 0));

        $svc->confirm($seekerActor, $interviewId);
        $completed = $svc->complete($employerActor, $interviewId);
        $this->assertTrue($completed['success']);
        $this->assertSame('completed', $completed['interview']['status'] ?? null);
    }

    public function testBlockNonShortlistedAndCancel(): void
    {
        if (!$this->schemaReady()) {
            $this->markTestSkipped('Migration 068 not applied');
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
            'title' => 'Reviewing Only ' . bin2hex(random_bytes(2)),
            'description' => 'Not shortlisted yet.',
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

        $svc = $this->container->get(InterviewSchedulingService::class);
        $blocked = $svc->schedule($employerActor, $appId, [
            'scheduled_at' => $this->futureLocal(),
            'timezone' => 'Asia/Colombo',
        ]);
        $this->assertFalse($blocked['success']);
        $this->assertStringContainsString('shortlisted', strtolower((string) ($blocked['message'] ?? '')));

        $apps->updateStatus($employerActor, $appId, ['status' => 'shortlisted']);
        $created = $svc->schedule($employerActor, $appId, [
            'scheduled_at' => $this->futureLocal(),
            'timezone' => 'UTC',
            'scheduled_at_is_utc' => true,
        ]);
        $this->assertTrue($created['success']);
        $id = (int) ($created['interview']['id'] ?? 0);
        $cancelled = $svc->cancel($employerActor, $id);
        $this->assertTrue($cancelled['success']);
        $this->assertSame('cancelled', $cancelled['interview']['status'] ?? null);

        // After cancel, can schedule again
        $again = $svc->schedule($employerActor, $appId, [
            'scheduled_at' => $this->futureLocal(),
            'timezone' => 'Asia/Colombo',
        ]);
        $this->assertTrue($again['success']);
    }

    public function testOwnershipIsolation(): void
    {
        if (!$this->schemaReady()) {
            $this->markTestSkipped('Migration 068 not applied');
        }
        $fx = $this->shortlistedFixture();
        $svc = $this->container->get(InterviewSchedulingService::class);
        $employerActor = ['id' => $fx['employer']['id'], 'role' => 'employer'];
        $created = $svc->schedule($employerActor, $fx['application_id'], [
            'scheduled_at' => $this->futureLocal(),
            'timezone' => 'Asia/Colombo',
        ]);
        $id = (int) ($created['interview']['id'] ?? 0);

        $otherSeeker = $svc->confirm(['id' => $fx['seeker']['id'] + 99999, 'role' => 'seeker'], $id);
        $this->assertFalse($otherSeeker['success']);

        $seekerCannotSchedule = $svc->schedule(
            ['id' => $fx['seeker']['id'], 'role' => 'seeker'],
            $fx['application_id'],
            ['scheduled_at' => $this->futureLocal(), 'timezone' => 'Asia/Colombo']
        );
        $this->assertFalse($seekerCannotSchedule['success']);
    }
}

<?php

declare(strict_types=1);

namespace JobVisa\Tests\Api;

use App\Core\Database;
use JobVisa\App\Domain\Application\Services\ApplicationService;
use JobVisa\App\Domain\Job\Services\EmployerJobsService;
use JobVisa\App\Domain\JobOffer\Services\JobOfferService;
use JobVisa\App\Domain\JobOffer\Validators\JobOfferValidator;
use JobVisa\App\Repositories\Contracts\ResumeRepositoryInterface;
use JobVisa\Tests\Support\ApplicationTestCase;
use PDO;
use Throwable;

final class JobOffersApiTest extends ApplicationTestCase
{
    private function schemaReady(): bool
    {
        try {
            Database::query('SELECT 1 FROM job_offers LIMIT 1');
            Database::query('SELECT 1 FROM job_offer_history LIMIT 1');

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
            'title' => 'Offer Job ' . bin2hex(random_bytes(3)),
            'description' => 'For job offer tests.',
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

    public function testExpiryUtcParsing(): void
    {
        $v = new JobOfferValidator();
        $utc = $v->parseToUtc('2026-08-20 23:59:59', true);
        $this->assertNotNull($utc);
        $this->assertSame('UTC', $utc->getTimezone()->getName());
        $this->assertSame('23:59:59', $utc->format('H:i:s'));
        $this->assertTrue($v->isExpired('2000-01-01 00:00:00', 'sent'));
        $this->assertFalse($v->isExpired('2099-01-01 00:00:00', 'sent'));
        $this->assertFalse($v->isExpired('2000-01-01 00:00:00', 'draft'));
    }

    public function testDraftSendAcceptHiresApplication(): void
    {
        if (!$this->schemaReady()) {
            $this->markTestSkipped('Migration 069 not applied');
        }

        $fx = $this->shortlistedFixture();
        $svc = $this->container->get(JobOfferService::class);
        $employerActor = ['id' => $fx['employer']['id'], 'role' => 'employer'];
        $seekerActor = ['id' => $fx['seeker']['id'], 'role' => 'seeker'];

        $created = $svc->create($employerActor, $fx['application_id'], [
            'salary_amount' => 175000,
            'salary_currency' => 'LKR',
            'pay_period' => 'monthly',
            'start_date' => '2026-09-01',
            'expires_at' => '2099-12-31 23:59:59',
            'expires_at_is_utc' => true,
            'notes' => 'Phase 1 offer',
        ]);
        $this->assertTrue($created['success']);
        $this->assertSame('draft', $created['offer']['status'] ?? null);
        $offerId = (int) ($created['offer']['id'] ?? 0);

        $dup = $svc->create($employerActor, $fx['application_id'], [
            'salary_amount' => 180000,
            'salary_currency' => 'LKR',
        ]);
        $this->assertFalse($dup['success']);
        $this->assertTrue($dup['conflict'] ?? false);

        // Seeker cannot see draft
        try {
            $svc->getForActor($seekerActor, $offerId);
            $this->fail('Seeker should not see draft offer');
        } catch (Throwable $e) {
            $this->assertStringContainsString('not found', strtolower($e->getMessage()));
        }

        $sent = $svc->send($employerActor, $offerId);
        $this->assertTrue($sent['success']);
        $this->assertSame('sent', $sent['offer']['status'] ?? null);

        $accepted = $svc->accept($seekerActor, $offerId);
        $this->assertTrue($accepted['success']);
        $this->assertSame('accepted', $accepted['offer']['status'] ?? null);
        $this->assertSame('hired', $accepted['offer']['application_status'] ?? null);
    }

    public function testBlockNonShortlistedWithdrawAndDecline(): void
    {
        if (!$this->schemaReady()) {
            $this->markTestSkipped('Migration 069 not applied');
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
            'title' => 'Reviewing Offer Block ' . bin2hex(random_bytes(2)),
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

        $svc = $this->container->get(JobOfferService::class);
        $blocked = $svc->create($employerActor, $appId, [
            'salary_amount' => 100000,
            'salary_currency' => 'LKR',
        ]);
        $this->assertFalse($blocked['success']);
        $this->assertStringContainsString('shortlisted', strtolower((string) ($blocked['message'] ?? '')));

        $apps->updateStatus($employerActor, $appId, ['status' => 'shortlisted']);
        $created = $svc->create($employerActor, $appId, [
            'salary_amount' => 120000,
            'salary_currency' => 'USD',
            'pay_period' => 'yearly',
        ]);
        $this->assertTrue($created['success']);
        $id = (int) ($created['offer']['id'] ?? 0);

        $withdrawn = $svc->withdraw($employerActor, $id);
        $this->assertTrue($withdrawn['success']);
        $this->assertSame('withdrawn', $withdrawn['offer']['status'] ?? null);

        // After withdraw, can draft again then send + decline
        $again = $svc->create($employerActor, $appId, [
            'salary_amount' => 125000,
            'salary_currency' => 'LKR',
            'expires_at' => '2099-06-01 12:00:00',
            'expires_at_is_utc' => true,
        ]);
        $this->assertTrue($again['success']);
        $id2 = (int) ($again['offer']['id'] ?? 0);
        $svc->send($employerActor, $id2);
        $declined = $svc->decline($seekerActor, $id2);
        $this->assertTrue($declined['success']);
        $this->assertSame('declined', $declined['offer']['status'] ?? null);
    }

    public function testOwnershipIsolationAndExpire(): void
    {
        if (!$this->schemaReady()) {
            $this->markTestSkipped('Migration 069 not applied');
        }
        $fx = $this->shortlistedFixture();
        $svc = $this->container->get(JobOfferService::class);
        $employerActor = ['id' => $fx['employer']['id'], 'role' => 'employer'];
        $created = $svc->create($employerActor, $fx['application_id'], [
            'salary_amount' => 99000,
            'salary_currency' => 'LKR',
        ]);
        $id = (int) ($created['offer']['id'] ?? 0);
        $svc->send($employerActor, $id);

        $otherSeeker = $svc->accept(['id' => $fx['seeker']['id'] + 99999, 'role' => 'seeker'], $id);
        $this->assertFalse($otherSeeker['success']);

        $seekerCannotCreate = $svc->create(
            ['id' => $fx['seeker']['id'], 'role' => 'seeker'],
            $fx['application_id'],
            ['salary_amount' => 1, 'salary_currency' => 'LKR']
        );
        $this->assertFalse($seekerCannotCreate['success']);

        $expired = $svc->expire($employerActor, $id);
        $this->assertTrue($expired['success']);
        $this->assertSame('expired', $expired['offer']['status'] ?? null);

        $cannotAccept = $svc->accept(['id' => $fx['seeker']['id'], 'role' => 'seeker'], $id);
        $this->assertFalse($cannotAccept['success']);
    }
}

<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Application\Services;

use JobVisa\App\Domain\Api\Resources\ApiResource;
use JobVisa\App\Domain\Application\Exceptions\ApplicationException;
use JobVisa\App\Domain\Application\Policies\ApplicationPolicy;
use JobVisa\App\Domain\Application\Validators\ApplicationValidator;
use JobVisa\App\Domain\Support\AbstractDomainService;
use JobVisa\App\Repositories\Contracts\ApplicationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\JobRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeRepositoryInterface;
use PDO;
use PDOException;
use Throwable;

/**
 * Job application lifecycle (Phase 1): apply, list, withdraw, employer status updates.
 */
final class ApplicationService extends AbstractDomainService
{
    public function __construct(
        private readonly ApplicationRepositoryInterface $applications,
        private readonly JobRepositoryInterface $jobs,
        private readonly ResumeRepositoryInterface $resumes,
        private readonly ApplicationPolicy $policy,
        private readonly ApplicationValidator $validator,
        private readonly PDO $pdo,
    ) {
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, errors?: array<string, list<string>>, application?: array<string, mixed>, conflict?: bool}
     */
    public function apply(array $actor, int $jobId, array $input): array
    {
        if (!$this->policy->canApply($actor)) {
            return ['success' => false, 'message' => ApplicationException::forbidden()->getMessage()];
        }

        $errors = $this->validator->fieldErrors($input, 'apply');
        if ($errors !== []) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors];
        }

        $job = $this->jobs->findPublishedRecordById($jobId);
        if ($job === null) {
            return ['success' => false, 'message' => ApplicationException::jobNotOpen()->getMessage()];
        }

        $userId = (int) ($actor['id'] ?? 0);
        $resumeId = isset($input['resume_id']) && $input['resume_id'] !== '' && $input['resume_id'] !== null
            ? (int) $input['resume_id']
            : 0;
        if ($resumeId < 1) {
            $primary = $this->resumes->findPrimaryByUserId($userId);
            $resumeId = (int) ($primary['id'] ?? 0);
        }
        if ($resumeId < 1) {
            return [
                'success' => false,
                'message' => ApplicationException::resumeRequired()->getMessage(),
                'errors' => ['resume_id' => [ApplicationException::resumeRequired()->getMessage()]],
            ];
        }
        $ownedResume = $this->resumes->findByIdForUser($resumeId, $userId);
        if ($ownedResume === null) {
            return [
                'success' => false,
                'message' => ApplicationException::resumeNotOwned()->getMessage(),
                'errors' => ['resume_id' => [ApplicationException::resumeNotOwned()->getMessage()]],
            ];
        }

        $coverLetter = null;
        if (array_key_exists('cover_letter', $input) && $input['cover_letter'] !== null) {
            $trimmed = trim((string) $input['cover_letter']);
            $coverLetter = $trimmed !== '' ? $trimmed : null;
        }

        $existing = $this->applications->findByJobAndUser($jobId, $userId);
        if ($existing !== null) {
            $status = (string) ($existing['status'] ?? '');
            if ($status !== 'withdrawn') {
                return [
                    'success' => false,
                    'message' => ApplicationException::duplicate()->getMessage(),
                    'conflict' => true,
                    'application' => ApiResource::applicationSeeker($existing),
                ];
            }

            return $this->reopenWithdrawn($actor, $existing, $resumeId, $coverLetter);
        }

        try {
            $this->pdo->beginTransaction();
            $id = $this->applications->insertApplication([
                'job_id' => $jobId,
                'user_id' => $userId,
                'resume_id' => $resumeId,
                'cover_letter' => $coverLetter,
                'status' => 'submitted',
            ]);
            $this->applications->insertStatusHistory($id, null, 'submitted', $userId, 'Application submitted');
            $this->applications->incrementJobApplicationsCount($jobId);
            $this->pdo->commit();
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            // Unique race
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                return [
                    'success' => false,
                    'message' => ApplicationException::duplicate()->getMessage(),
                    'conflict' => true,
                ];
            }
            throw $e;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }

        $row = $this->applications->findDetailedRecordById($id);

        return [
            'success' => true,
            'message' => 'Application submitted.',
            'application' => ApiResource::applicationSeeker($row ?? ['id' => $id, 'status' => 'submitted']),
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return list<array<string, mixed>>
     */
    public function listForSeeker(array $actor, int $limit = 100): array
    {
        if (!$this->policy->canApply($actor)) {
            throw ApplicationException::forbidden();
        }
        $rows = $this->applications->findDetailedByUserId((int) ($actor['id'] ?? 0), $limit);

        return array_map(
            static fn (array $r): array => ApiResource::applicationSeeker($r),
            $rows
        );
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    public function getForSeeker(array $actor, int $applicationId): array
    {
        $row = $this->applications->findDetailedRecordById($applicationId);
        if ($row === null || !$this->policy->canViewOwn($actor, $row)) {
            throw ApplicationException::notFound();
        }

        return ApiResource::applicationSeeker($row, true);
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, application?: array<string, mixed>}
     */
    public function withdraw(array $actor, int $applicationId): array
    {
        $row = $this->applications->findDetailedRecordById($applicationId);
        if ($row === null || (int) ($row['user_id'] ?? 0) !== (int) ($actor['id'] ?? 0)) {
            return ['success' => false, 'message' => ApplicationException::notFound()->getMessage()];
        }
        if (!$this->policy->canWithdraw($actor, $row)) {
            $status = (string) ($row['status'] ?? '');
            if (!in_array($status, ['submitted', 'reviewing'], true)) {
                return [
                    'success' => false,
                    'message' => ApplicationException::invalidTransition($status, 'withdrawn')->getMessage(),
                ];
            }

            return ['success' => false, 'message' => ApplicationException::forbidden()->getMessage()];
        }

        $from = (string) ($row['status'] ?? 'submitted');
        $this->pdo->beginTransaction();
        try {
            $this->applications->updateApplicationStatus($applicationId, 'withdrawn', null);
            $this->applications->insertStatusHistory(
                $applicationId,
                $from,
                'withdrawn',
                (int) ($actor['id'] ?? 0),
                'Withdrawn by applicant'
            );
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }

        $updated = $this->applications->findDetailedRecordById($applicationId);

        return [
            'success' => true,
            'message' => 'Application withdrawn.',
            'application' => ApiResource::applicationSeeker($updated ?? $row, true),
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return list<array<string, mixed>>
     */
    public function listForEmployerJob(array $actor, int $jobId, int $limit = 200): array
    {
        $job = $this->requireOwnedJob($actor, $jobId);
        $rows = $this->applications->findDetailedByJobId($jobId, $limit);

        return array_map(
            static fn (array $r): array => ApiResource::applicant($r),
            $rows
        );
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    public function getForEmployer(array $actor, int $applicationId): array
    {
        $row = $this->applications->findDetailedRecordById($applicationId);
        if ($row === null) {
            throw ApplicationException::notFound();
        }
        $this->requireOwnedJob($actor, (int) ($row['job_id'] ?? 0));

        return ApiResource::applicant($row, true);
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, errors?: array<string, list<string>>, application?: array<string, mixed>}
     */
    public function updateStatus(array $actor, int $applicationId, array $input): array
    {
        $errors = $this->validator->fieldErrors($input, 'status');
        if ($errors !== []) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors];
        }

        $row = $this->applications->findDetailedRecordById($applicationId);
        if ($row === null) {
            return ['success' => false, 'message' => ApplicationException::notFound()->getMessage()];
        }

        try {
            $this->requireOwnedJob($actor, (int) ($row['job_id'] ?? 0));
        } catch (ApplicationException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        $from = (string) ($row['status'] ?? '');
        $to = (string) ($input['status'] ?? '');
        if ($to === 'withdrawn') {
            return [
                'success' => false,
                'message' => 'Employers cannot set withdrawn status.',
                'errors' => ['status' => ['Employers cannot set withdrawn status.']],
            ];
        }
        if (!$this->validator->canEmployerTransition($from, $to)) {
            return [
                'success' => false,
                'message' => ApplicationException::invalidTransition($from, $to)->getMessage(),
                'errors' => ['status' => [ApplicationException::invalidTransition($from, $to)->getMessage()]],
            ];
        }

        $notes = null;
        if (array_key_exists('employer_notes', $input)) {
            $raw = $input['employer_notes'];
            $notes = $raw === null || $raw === '' ? null : trim((string) $raw);
        } else {
            $notes = $row['employer_notes'] ?? null;
        }

        $this->pdo->beginTransaction();
        try {
            $this->applications->updateApplicationStatus($applicationId, $to, is_string($notes) ? $notes : null);
            $this->applications->insertStatusHistory(
                $applicationId,
                $from,
                $to,
                (int) ($actor['id'] ?? 0),
                $notes
            );
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }

        $updated = $this->applications->findDetailedRecordById($applicationId);

        return [
            'success' => true,
            'message' => 'Application status updated.',
            'application' => ApiResource::applicant($updated ?? $row, true),
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $existing
     * @return array{success: bool, message: string, application?: array<string, mixed>}
     */
    private function reopenWithdrawn(array $actor, array $existing, int $resumeId, ?string $coverLetter): array
    {
        $id = (int) ($existing['id'] ?? 0);
        $userId = (int) ($actor['id'] ?? 0);
        $this->pdo->beginTransaction();
        try {
            $this->applications->reopenApplication($id, $resumeId, $coverLetter);
            $this->applications->insertStatusHistory(
                $id,
                'withdrawn',
                'submitted',
                $userId,
                'Re-applied after withdrawal'
            );
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }

        $row = $this->applications->findDetailedRecordById($id);

        return [
            'success' => true,
            'message' => 'Application re-submitted.',
            'application' => ApiResource::applicationSeeker($row ?? $existing, true),
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    private function requireOwnedJob(array $actor, int $jobId): array
    {
        if ((string) ($actor['role'] ?? '') !== 'employer') {
            throw ApplicationException::forbidden();
        }
        $job = $this->jobs->findOwnedByEmployerUser($jobId, (int) ($actor['id'] ?? 0));
        if ($job === null) {
            throw ApplicationException::notFound();
        }
        if (!$this->policy->canManageAsEmployer($actor, $job)) {
            throw ApplicationException::forbidden();
        }

        return $job;
    }
}

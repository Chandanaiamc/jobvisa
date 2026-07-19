<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\HiringCompletion\Services;

use JobVisa\App\Domain\Api\Resources\ApiResource;
use JobVisa\App\Domain\HiringCompletion\Exceptions\HiringCompletionException;
use JobVisa\App\Domain\HiringCompletion\Policies\HiringCompletionPolicy;
use JobVisa\App\Domain\HiringCompletion\Validators\HiringCompletionValidator;
use JobVisa\App\Repositories\Contracts\ApplicationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\HireCompletionRepositoryInterface;
use JobVisa\App\Repositories\Contracts\JobRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ScheduledInterviewRepositoryInterface;
use PDO;
use PDOException;
use Throwable;

/**
 * Hiring completion Phase 1 (Option B): pending → confirmed → completed (+ cancel).
 */
final class HiringCompletionService
{
    public function __construct(
        private readonly HireCompletionRepositoryInterface $hires,
        private readonly ApplicationRepositoryInterface $applications,
        private readonly JobRepositoryInterface $jobs,
        private readonly ScheduledInterviewRepositoryInterface $interviews,
        private readonly HiringCompletionPolicy $policy,
        private readonly HiringCompletionValidator $validator,
        private readonly PDO $pdo,
    ) {
    }

    /**
     * Idempotent: create pending hire completion after offer accept (caller may be in a transaction).
     *
     * @param  array<string, mixed>  $application  app row (needs id, job_id, user_id)
     * @param  array<string, mixed>  $offer        offer row (needs id, employer_user_id, candidate_user_id, start_date?)
     * @return array<string, mixed>|null
     */
    public function ensurePendingFromOfferAccept(
        array $application,
        array $offer,
        int $actorUserId
    ): ?array {
        $applicationId = (int) ($application['id'] ?? 0);
        $offerId = (int) ($offer['id'] ?? 0);
        if ($applicationId < 1 || $offerId < 1) {
            return null;
        }

        $existing = $this->hires->findByApplicationId($applicationId);
        if ($existing !== null) {
            if (empty($existing['offer_id']) && $offerId > 0) {
                $this->hires->updateById((int) $existing['id'], ['offer_id' => $offerId]);

                return $this->hires->findDetailedById((int) $existing['id']) ?? $existing;
            }

            return $existing;
        }

        $startDate = null;
        if (!empty($offer['start_date'])) {
            $startDate = $this->validator->parseDate((string) $offer['start_date']);
        }

        return $this->insertPending([
            'application_id' => $applicationId,
            'job_id' => (int) ($offer['job_id'] ?? $application['job_id'] ?? 0),
            'employer_user_id' => (int) ($offer['employer_user_id'] ?? 0),
            'candidate_user_id' => (int) ($offer['candidate_user_id'] ?? $application['user_id'] ?? 0),
            'offer_id' => $offerId,
            'start_date' => $startDate,
            'notes' => null,
        ], $actorUserId, 'Created from accepted offer');
    }

    /**
     * Idempotent: create pending hire completion when employer sets application to hired.
     *
     * @param  array<string, mixed>  $application
     * @return array<string, mixed>|null
     */
    public function ensurePendingFromEmployerHire(array $application, int $actorUserId): ?array
    {
        $applicationId = (int) ($application['id'] ?? 0);
        if ($applicationId < 1) {
            return null;
        }

        $existing = $this->hires->findByApplicationId($applicationId);
        if ($existing !== null) {
            return $existing;
        }

        $jobId = (int) ($application['job_id'] ?? 0);
        $employerUserId = (int) ($application['employer_user_id'] ?? 0);
        if ($employerUserId < 1 && $jobId > 0) {
            $job = $this->jobs->findOwnedByEmployerUser($jobId, $actorUserId);
            $employerUserId = (int) ($job['employer_user_id'] ?? $actorUserId);
        }

        return $this->insertPending([
            'application_id' => $applicationId,
            'job_id' => $jobId,
            'employer_user_id' => $employerUserId > 0 ? $employerUserId : $actorUserId,
            'candidate_user_id' => (int) ($application['user_id'] ?? 0),
            'offer_id' => null,
            'start_date' => null,
            'notes' => null,
        ], $actorUserId, 'Created from employer hire');
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return list<array<string, mixed>>
     */
    public function listForEmployer(array $actor, int $limit = 100): array
    {
        if ((string) ($actor['role'] ?? '') !== 'employer') {
            throw HiringCompletionException::forbidden();
        }
        $rows = $this->hires->listForEmployerUser((int) ($actor['id'] ?? 0), $limit);

        return array_map(static fn (array $r): array => ApiResource::hireCompletion($r, false), $rows);
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return list<array<string, mixed>>
     */
    public function listForSeeker(array $actor, int $limit = 100): array
    {
        if ((string) ($actor['role'] ?? '') !== 'seeker') {
            throw HiringCompletionException::forbidden();
        }
        $rows = $this->hires->listForCandidateUser((int) ($actor['id'] ?? 0), $limit);

        return array_map(static fn (array $r): array => ApiResource::hireCompletion($r, false), $rows);
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    public function getForActor(array $actor, int $hireId): array
    {
        $row = $this->hires->findDetailedById($hireId);
        if ($row === null) {
            throw HiringCompletionException::notFound();
        }

        $role = (string) ($actor['role'] ?? '');
        if ($role === 'employer') {
            $job = $this->jobs->findOwnedByEmployerUser((int) ($row['job_id'] ?? 0), (int) ($actor['id'] ?? 0));
            if ($job === null || !$this->policy->canManageAsEmployer($actor, $row, $job)) {
                throw HiringCompletionException::notFound();
            }
        } elseif ($role === 'seeker') {
            if (!$this->policy->canViewAsSeeker($actor, $row)) {
                throw HiringCompletionException::notFound();
            }
        } else {
            throw HiringCompletionException::forbidden();
        }

        return ApiResource::hireCompletion($row, true);
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, errors?: array<string, list<string>>, hire_completion?: array<string, mixed>}
     */
    public function confirm(array $actor, int $hireId, array $input = []): array
    {
        $errors = $this->validator->fieldErrors($input, 'confirm');
        if ($errors !== []) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors];
        }

        try {
            $row = $this->requireEmployerHire($actor, $hireId);
        } catch (HiringCompletionException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        $from = (string) ($row['status'] ?? '');
        if ($from !== 'pending') {
            return [
                'success' => false,
                'message' => HiringCompletionException::invalidTransition($from, 'confirmed')->getMessage(),
            ];
        }

        $fields = [
            'status' => 'confirmed',
            'confirmed_at' => gmdate('Y-m-d H:i:s.v'),
        ];
        if (array_key_exists('start_date', $input)) {
            $raw = trim((string) ($input['start_date'] ?? ''));
            $fields['start_date'] = $raw !== '' ? $this->validator->parseDate($raw) : null;
        }
        if (array_key_exists('notes', $input)) {
            $trimmed = trim((string) ($input['notes'] ?? ''));
            $fields['notes'] = $trimmed !== '' ? mb_substr($trimmed, 0, 500) : null;
        }

        return $this->transition($actor, $row, 'confirmed', 'Hire confirmed.', $fields, 'Confirmed by employer');
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, errors?: array<string, list<string>>, hire_completion?: array<string, mixed>, job_closed?: bool}
     */
    public function complete(array $actor, int $hireId, array $input = []): array
    {
        $errors = $this->validator->fieldErrors($input, 'complete');
        if ($errors !== []) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors];
        }

        try {
            $row = $this->requireEmployerHire($actor, $hireId);
        } catch (HiringCompletionException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        $from = (string) ($row['status'] ?? '');
        if ($from !== 'confirmed') {
            return [
                'success' => false,
                'message' => HiringCompletionException::invalidTransition($from, 'completed')->getMessage(),
            ];
        }

        $fields = [
            'status' => 'completed',
            'completed_at' => gmdate('Y-m-d H:i:s.v'),
        ];
        if (array_key_exists('start_date', $input)) {
            $raw = trim((string) ($input['start_date'] ?? ''));
            $fields['start_date'] = $raw !== '' ? $this->validator->parseDate($raw) : ($row['start_date'] ?? null);
        }
        if (array_key_exists('notes', $input)) {
            $trimmed = trim((string) ($input['notes'] ?? ''));
            $fields['notes'] = $trimmed !== '' ? mb_substr($trimmed, 0, 500) : ($row['notes'] ?? null);
        }

        $jobClosed = false;
        $id = (int) ($row['id'] ?? 0);
        $applicationId = (int) ($row['application_id'] ?? 0);
        $jobId = (int) ($row['job_id'] ?? 0);
        $actorId = (int) ($actor['id'] ?? 0);

        $this->pdo->beginTransaction();
        try {
            $this->hires->updateById($id, $fields);
            $this->hires->insertHistory($id, $from, 'completed', $actorId, 'Completed by employer');

            // Soft-cancel open interviews for this application.
            $this->interviews->cancelActiveByApplicationId(
                $applicationId,
                $actorId,
                'Cancelled due to hire completion'
            );

            // Auto-close job when hired applications fill vacancies.
            if ($jobId > 0) {
                $job = $this->jobs->findOwnedByEmployerUser($jobId, $actorId);
                if ($job !== null && (string) ($job['status'] ?? '') === 'published') {
                    $vacancies = max(1, (int) ($job['vacancies'] ?? 1));
                    $hiredCount = $this->applications->countHiredByJobId($jobId);
                    if ($hiredCount >= $vacancies) {
                        $this->jobs->updateJobById($jobId, [
                            'status' => 'closed',
                            'closes_at' => gmdate('Y-m-d H:i:s.v'),
                        ]);
                        $jobClosed = true;
                    }
                }
            }

            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }

        $updated = $this->hires->findDetailedById($id);

        return [
            'success' => true,
            'message' => $jobClosed ? 'Hire completed. Job closed (vacancies filled).' : 'Hire completed.',
            'hire_completion' => ApiResource::hireCompletion($updated ?? $row, true),
            'job_closed' => $jobClosed,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, hire_completion?: array<string, mixed>}
     */
    public function cancel(array $actor, int $hireId, ?string $note = null): array
    {
        try {
            $row = $this->requireEmployerHire($actor, $hireId);
        } catch (HiringCompletionException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        $from = (string) ($row['status'] ?? '');
        if (!in_array($from, ['pending', 'confirmed'], true)) {
            return [
                'success' => false,
                'message' => HiringCompletionException::invalidTransition($from, 'cancelled')->getMessage(),
            ];
        }

        return $this->transition($actor, $row, 'cancelled', 'Hire completion cancelled.', [
            'cancelled_at' => gmdate('Y-m-d H:i:s.v'),
        ], $note ?? 'Cancelled by employer');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    private function insertPending(array $data, int $actorUserId, string $note): ?array
    {
        if ((int) ($data['application_id'] ?? 0) < 1 || (int) ($data['job_id'] ?? 0) < 1) {
            return null;
        }
        if ((int) ($data['candidate_user_id'] ?? 0) < 1 || (int) ($data['employer_user_id'] ?? 0) < 1) {
            return null;
        }

        try {
            $id = $this->hires->insert([
                'application_id' => (int) $data['application_id'],
                'job_id' => (int) $data['job_id'],
                'employer_user_id' => (int) $data['employer_user_id'],
                'candidate_user_id' => (int) $data['candidate_user_id'],
                'offer_id' => $data['offer_id'] ?? null,
                'status' => 'pending',
                'start_date' => $data['start_date'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
            $this->hires->insertHistory($id, null, 'pending', $actorUserId, $note);

            return $this->hires->findDetailedById($id) ?? $this->hires->findById($id);
        } catch (PDOException $e) {
            // Unique race: return existing
            $existing = $this->hires->findByApplicationId((int) $data['application_id']);
            if ($existing !== null) {
                return $existing;
            }
            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $extraFields
     * @return array{success: bool, message: string, hire_completion?: array<string, mixed>}
     */
    private function transition(
        array $actor,
        array $row,
        string $to,
        string $okMessage,
        array $extraFields,
        ?string $note
    ): array {
        $id = (int) ($row['id'] ?? 0);
        $from = (string) ($row['status'] ?? '');
        $fields = array_merge(['status' => $to], $extraFields);

        $this->pdo->beginTransaction();
        try {
            $this->hires->updateById($id, $fields);
            $this->hires->insertHistory($id, $from, $to, (int) ($actor['id'] ?? 0), $note);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }

        $updated = $this->hires->findDetailedById($id);

        return [
            'success' => true,
            'message' => $okMessage,
            'hire_completion' => ApiResource::hireCompletion($updated ?? $row, true),
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    private function requireEmployerHire(array $actor, int $hireId): array
    {
        $row = $this->hires->findDetailedById($hireId);
        if ($row === null) {
            throw HiringCompletionException::notFound();
        }
        $job = $this->jobs->findOwnedByEmployerUser((int) ($row['job_id'] ?? 0), (int) ($actor['id'] ?? 0));
        if ($job === null || !$this->policy->canManageAsEmployer($actor, $row, $job)) {
            throw HiringCompletionException::notFound();
        }

        return $row;
    }
}

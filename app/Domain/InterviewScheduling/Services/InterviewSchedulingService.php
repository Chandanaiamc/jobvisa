<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\InterviewScheduling\Services;

use DateTimeImmutable;
use DateTimeZone;
use JobVisa\App\Domain\Api\Resources\ApiResource;
use JobVisa\App\Domain\InterviewScheduling\Exceptions\InterviewSchedulingException;
use JobVisa\App\Domain\InterviewScheduling\Policies\InterviewSchedulingPolicy;
use JobVisa\App\Domain\InterviewScheduling\Validators\InterviewSchedulingValidator;
use JobVisa\App\Repositories\Contracts\ApplicationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\JobRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ScheduledInterviewRepositoryInterface;
use PDO;
use Throwable;

/**
 * Interview scheduling Phase 1 (Option B): propose, confirm/decline, reschedule, cancel, complete.
 */
final class InterviewSchedulingService
{
    public function __construct(
        private readonly ScheduledInterviewRepositoryInterface $interviews,
        private readonly ApplicationRepositoryInterface $applications,
        private readonly JobRepositoryInterface $jobs,
        private readonly InterviewSchedulingPolicy $policy,
        private readonly InterviewSchedulingValidator $validator,
        private readonly PDO $pdo,
    ) {
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, errors?: array<string, list<string>>, interview?: array<string, mixed>, conflict?: bool}
     */
    public function schedule(array $actor, int $applicationId, array $input): array
    {
        $errors = $this->validator->fieldErrors($input, 'schedule');
        if ($errors !== []) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors];
        }

        $app = $this->applications->findDetailedRecordById($applicationId);
        if ($app === null) {
            return ['success' => false, 'message' => InterviewSchedulingException::notFound()->getMessage()];
        }

        $job = $this->jobs->findOwnedByEmployerUser((int) ($app['job_id'] ?? 0), (int) ($actor['id'] ?? 0));
        if ($job === null || !$this->policy->canSchedule($actor, $job)) {
            return ['success' => false, 'message' => InterviewSchedulingException::notFound()->getMessage()];
        }

        if ((string) ($app['status'] ?? '') !== 'shortlisted') {
            return [
                'success' => false,
                'message' => InterviewSchedulingException::notShortlisted()->getMessage(),
                'errors' => ['application' => [InterviewSchedulingException::notShortlisted()->getMessage()]],
            ];
        }

        $active = $this->interviews->findActiveByApplicationId($applicationId);
        if ($active !== null) {
            return [
                'success' => false,
                'message' => InterviewSchedulingException::activeExists()->getMessage(),
                'conflict' => true,
                'interview' => ApiResource::scheduledInterview($active, true, $this->validator),
            ];
        }

        $payload = $this->normalizeScheduleFields($input);
        if ($payload === null) {
            return [
                'success' => false,
                'message' => InterviewSchedulingException::invalidScheduleTime()->getMessage(),
                'errors' => ['scheduled_at' => [InterviewSchedulingException::invalidScheduleTime()->getMessage()]],
            ];
        }

        try {
            $this->pdo->beginTransaction();
            // Re-check active inside transaction
            if ($this->interviews->findActiveByApplicationId($applicationId) !== null) {
                $this->pdo->rollBack();

                return [
                    'success' => false,
                    'message' => InterviewSchedulingException::activeExists()->getMessage(),
                    'conflict' => true,
                ];
            }

            $id = $this->interviews->insert([
                'application_id' => $applicationId,
                'job_id' => (int) ($app['job_id'] ?? 0),
                'employer_user_id' => (int) ($actor['id'] ?? 0),
                'candidate_user_id' => (int) ($app['user_id'] ?? 0),
                'status' => 'proposed',
                'scheduled_at_utc' => $payload['scheduled_at_utc'],
                'duration_minutes' => $payload['duration_minutes'],
                'timezone' => $payload['timezone'],
                'location_type' => $payload['location_type'],
                'location_notes' => $payload['location_notes'],
                'round_number' => 1,
            ]);
            $this->interviews->insertHistory($id, null, 'proposed', (int) ($actor['id'] ?? 0), 'Interview proposed');
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }

        $row = $this->interviews->findDetailedById($id);

        return [
            'success' => true,
            'message' => 'Interview scheduled.',
            'interview' => ApiResource::scheduledInterview($row ?? ['id' => $id], true, $this->validator),
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return list<array<string, mixed>>
     */
    public function listForEmployer(array $actor, int $limit = 100): array
    {
        if ((string) ($actor['role'] ?? '') !== 'employer') {
            throw InterviewSchedulingException::forbidden();
        }
        $rows = $this->interviews->listForEmployerUser((int) ($actor['id'] ?? 0), $limit);

        return array_map(
            fn (array $r): array => ApiResource::scheduledInterview($r, false, $this->validator),
            $rows
        );
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return list<array<string, mixed>>
     */
    public function listForSeeker(array $actor, int $limit = 100): array
    {
        if ((string) ($actor['role'] ?? '') !== 'seeker') {
            throw InterviewSchedulingException::forbidden();
        }
        $rows = $this->interviews->listForCandidateUser((int) ($actor['id'] ?? 0), $limit);

        return array_map(
            fn (array $r): array => ApiResource::scheduledInterview($r, false, $this->validator),
            $rows
        );
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    public function getForActor(array $actor, int $interviewId): array
    {
        $row = $this->interviews->findDetailedById($interviewId);
        if ($row === null) {
            throw InterviewSchedulingException::notFound();
        }

        $role = (string) ($actor['role'] ?? '');
        if ($role === 'employer') {
            $job = $this->jobs->findOwnedByEmployerUser((int) ($row['job_id'] ?? 0), (int) ($actor['id'] ?? 0));
            if ($job === null || !$this->policy->canManageAsEmployer($actor, $row, $job)) {
                throw InterviewSchedulingException::notFound();
            }
        } elseif ($role === 'seeker') {
            if (!$this->policy->canViewAsSeeker($actor, $row)) {
                throw InterviewSchedulingException::notFound();
            }
        } else {
            throw InterviewSchedulingException::forbidden();
        }

        return ApiResource::scheduledInterview($row, true, $this->validator);
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, errors?: array<string, list<string>>, interview?: array<string, mixed>}
     */
    public function reschedule(array $actor, int $interviewId, array $input): array
    {
        $errors = $this->validator->fieldErrors($input, 'reschedule');
        if ($errors !== []) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors];
        }

        try {
            $row = $this->requireEmployerInterview($actor, $interviewId);
        } catch (InterviewSchedulingException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
        $from = (string) ($row['status'] ?? '');
        if (!in_array($from, ['proposed', 'confirmed'], true)) {
            return [
                'success' => false,
                'message' => InterviewSchedulingException::invalidTransition($from, 'proposed')->getMessage(),
                'errors' => ['status' => [InterviewSchedulingException::invalidTransition($from, 'proposed')->getMessage()]],
            ];
        }

        $payload = $this->normalizeScheduleFields($input, $row);
        if ($payload === null) {
            return [
                'success' => false,
                'message' => InterviewSchedulingException::invalidScheduleTime()->getMessage(),
                'errors' => ['scheduled_at' => [InterviewSchedulingException::invalidScheduleTime()->getMessage()]],
            ];
        }

        $this->pdo->beginTransaction();
        try {
            $this->interviews->updateById($interviewId, [
                'status' => 'proposed',
                'scheduled_at_utc' => $payload['scheduled_at_utc'],
                'duration_minutes' => $payload['duration_minutes'],
                'timezone' => $payload['timezone'],
                'location_type' => $payload['location_type'],
                'location_notes' => $payload['location_notes'],
                'round_number' => (int) ($row['round_number'] ?? 1) + 1,
                'cancelled_at' => null,
                'completed_at' => null,
            ]);
            $this->interviews->insertHistory(
                $interviewId,
                $from,
                'proposed',
                (int) ($actor['id'] ?? 0),
                'Interview rescheduled'
            );
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }

        $updated = $this->interviews->findDetailedById($interviewId);

        return [
            'success' => true,
            'message' => 'Interview rescheduled.',
            'interview' => ApiResource::scheduledInterview($updated ?? $row, true, $this->validator),
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, interview?: array<string, mixed>}
     */
    public function cancel(array $actor, int $interviewId, ?string $note = null): array
    {
        try {
            $row = $this->requireEmployerInterview($actor, $interviewId);
        } catch (InterviewSchedulingException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
        $from = (string) ($row['status'] ?? '');
        if (!in_array($from, ['proposed', 'confirmed'], true)) {
            return [
                'success' => false,
                'message' => InterviewSchedulingException::invalidTransition($from, 'cancelled')->getMessage(),
            ];
        }

        return $this->transitionEmployer($actor, $row, 'cancelled', 'Interview cancelled.', [
            'cancelled_at' => gmdate('Y-m-d H:i:s.v'),
        ], $note ?? 'Cancelled by employer');
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, interview?: array<string, mixed>}
     */
    public function complete(array $actor, int $interviewId, ?string $note = null): array
    {
        try {
            $row = $this->requireEmployerInterview($actor, $interviewId);
        } catch (InterviewSchedulingException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
        $from = (string) ($row['status'] ?? '');
        if ($from !== 'confirmed') {
            return [
                'success' => false,
                'message' => InterviewSchedulingException::invalidTransition($from, 'completed')->getMessage(),
            ];
        }

        return $this->transitionEmployer($actor, $row, 'completed', 'Interview completed.', [
            'completed_at' => gmdate('Y-m-d H:i:s.v'),
        ], $note ?? 'Marked completed');
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, interview?: array<string, mixed>}
     */
    public function confirm(array $actor, int $interviewId): array
    {
        return $this->seekerRespond($actor, $interviewId, 'confirmed', 'Interview confirmed.');
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, interview?: array<string, mixed>}
     */
    public function decline(array $actor, int $interviewId): array
    {
        return $this->seekerRespond($actor, $interviewId, 'declined', 'Interview declined.');
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, interview?: array<string, mixed>}
     */
    private function seekerRespond(array $actor, int $interviewId, string $to, string $okMessage): array
    {
        $row = $this->interviews->findDetailedById($interviewId);
        if ($row === null || !$this->policy->canRespondAsSeeker($actor, $row)) {
            return ['success' => false, 'message' => InterviewSchedulingException::notFound()->getMessage()];
        }
        $from = (string) ($row['status'] ?? '');
        if ($from !== 'proposed') {
            return [
                'success' => false,
                'message' => InterviewSchedulingException::invalidTransition($from, $to)->getMessage(),
            ];
        }

        $this->pdo->beginTransaction();
        try {
            $this->interviews->updateById($interviewId, ['status' => $to]);
            $this->interviews->insertHistory($interviewId, $from, $to, (int) ($actor['id'] ?? 0), $okMessage);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }

        $updated = $this->interviews->findDetailedById($interviewId);

        return [
            'success' => true,
            'message' => $okMessage,
            'interview' => ApiResource::scheduledInterview($updated ?? $row, true, $this->validator),
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $extraFields
     * @return array{success: bool, message: string, interview?: array<string, mixed>}
     */
    private function transitionEmployer(
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
            $this->interviews->updateById($id, $fields);
            $this->interviews->insertHistory($id, $from, $to, (int) ($actor['id'] ?? 0), $note);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }

        $updated = $this->interviews->findDetailedById($id);

        return [
            'success' => true,
            'message' => $okMessage,
            'interview' => ApiResource::scheduledInterview($updated ?? $row, true, $this->validator),
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    private function requireEmployerInterview(array $actor, int $interviewId): array
    {
        $row = $this->interviews->findDetailedById($interviewId);
        if ($row === null) {
            throw InterviewSchedulingException::notFound();
        }
        $job = $this->jobs->findOwnedByEmployerUser((int) ($row['job_id'] ?? 0), (int) ($actor['id'] ?? 0));
        if ($job === null || !$this->policy->canManageAsEmployer($actor, $row, $job)) {
            throw InterviewSchedulingException::notFound();
        }

        return $row;
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>|null  $existing
     * @return array{scheduled_at_utc: string, duration_minutes: int, timezone: string, location_type: string, location_notes: ?string}|null
     */
    private function normalizeScheduleFields(array $input, ?array $existing = null): ?array
    {
        $timezone = trim((string) ($input['timezone'] ?? ($existing['timezone'] ?? '')));
        if ($timezone === '' || !$this->validator->isValidTimezone($timezone)) {
            return null;
        }

        $asUtc = !empty($input['scheduled_at_is_utc']) || array_key_exists('scheduled_at_utc', $input);
        $raw = (string) ($input['scheduled_at_utc'] ?? $input['scheduled_at'] ?? '');
        $utc = $this->validator->parseToUtc($raw, $timezone, $asUtc);
        if ($utc === null || $utc <= new DateTimeImmutable('now', new DateTimeZone('UTC'))) {
            return null;
        }

        $duration = isset($input['duration_minutes'])
            ? (int) $input['duration_minutes']
            : (int) ($existing['duration_minutes'] ?? 60);
        $duration = max(15, min(480, $duration > 0 ? $duration : 60));

        $locationType = (string) ($input['location_type'] ?? ($existing['location_type'] ?? 'other'));
        if (!in_array($locationType, InterviewSchedulingValidator::LOCATION_TYPES, true)) {
            $locationType = 'other';
        }

        $notes = null;
        if (array_key_exists('location_notes', $input)) {
            $trimmed = trim((string) ($input['location_notes'] ?? ''));
            $notes = $trimmed !== '' ? mb_substr($trimmed, 0, 500) : null;
        } else {
            $notes = isset($existing['location_notes']) ? (string) $existing['location_notes'] : null;
        }

        return [
            'scheduled_at_utc' => $this->validator->formatUtc($utc),
            'duration_minutes' => $duration,
            'timezone' => $timezone,
            'location_type' => $locationType,
            'location_notes' => $notes,
        ];
    }
}

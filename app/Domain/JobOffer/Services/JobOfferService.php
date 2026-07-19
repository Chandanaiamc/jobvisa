<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\JobOffer\Services;

use JobVisa\App\Domain\Api\Resources\ApiResource;
use JobVisa\App\Domain\HiringCompletion\Exceptions\HiringCompletionException;
use JobVisa\App\Domain\HiringCompletion\Services\HiringCompletionService;
use JobVisa\App\Domain\HiringCompletion\Validators\HiringCompletionValidator;
use JobVisa\App\Domain\JobOffer\Exceptions\JobOfferException;
use JobVisa\App\Domain\JobOffer\Policies\JobOfferPolicy;
use JobVisa\App\Domain\JobOffer\Validators\JobOfferValidator;
use JobVisa\App\Repositories\Contracts\ApplicationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\JobOfferRepositoryInterface;
use JobVisa\App\Repositories\Contracts\JobRepositoryInterface;
use PDO;
use Throwable;

/**
 * Job offer management Phase 1 (Option B): draft, send, accept/decline, withdraw, expire.
 */
final class JobOfferService
{
    public function __construct(
        private readonly JobOfferRepositoryInterface $offers,
        private readonly ApplicationRepositoryInterface $applications,
        private readonly JobRepositoryInterface $jobs,
        private readonly JobOfferPolicy $policy,
        private readonly JobOfferValidator $validator,
        private readonly HiringCompletionService $hiringCompletions,
        private readonly HiringCompletionValidator $hireCompletionValidator,
        private readonly PDO $pdo,
    ) {
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, errors?: array<string, list<string>>, offer?: array<string, mixed>, conflict?: bool}
     */
    public function create(array $actor, int $applicationId, array $input): array
    {
        $errors = $this->validator->fieldErrors($input, 'create');
        if ($errors !== []) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors];
        }

        $app = $this->applications->findDetailedRecordById($applicationId);
        if ($app === null) {
            return ['success' => false, 'message' => JobOfferException::notFound()->getMessage()];
        }

        $job = $this->jobs->findOwnedByEmployerUser((int) ($app['job_id'] ?? 0), (int) ($actor['id'] ?? 0));
        if ($job === null || !$this->policy->canCreate($actor, $job)) {
            return ['success' => false, 'message' => JobOfferException::notFound()->getMessage()];
        }

        if ((string) ($app['status'] ?? '') !== 'shortlisted') {
            return [
                'success' => false,
                'message' => JobOfferException::notShortlisted()->getMessage(),
                'errors' => ['application' => [JobOfferException::notShortlisted()->getMessage()]],
            ];
        }

        $active = $this->offers->findActiveByApplicationId($applicationId);
        if ($active !== null) {
            return [
                'success' => false,
                'message' => JobOfferException::activeExists()->getMessage(),
                'conflict' => true,
                'offer' => ApiResource::jobOffer($active, true),
            ];
        }

        $payload = $this->normalizeCreateFields($input);

        try {
            $this->pdo->beginTransaction();
            if ($this->offers->findActiveByApplicationId($applicationId) !== null) {
                $this->pdo->rollBack();

                return [
                    'success' => false,
                    'message' => JobOfferException::activeExists()->getMessage(),
                    'conflict' => true,
                ];
            }

            $id = $this->offers->insert([
                'application_id' => $applicationId,
                'job_id' => (int) ($app['job_id'] ?? 0),
                'employer_user_id' => (int) ($actor['id'] ?? 0),
                'candidate_user_id' => (int) ($app['user_id'] ?? 0),
                'status' => 'draft',
                'salary_amount' => $payload['salary_amount'],
                'salary_currency' => $payload['salary_currency'],
                'pay_period' => $payload['pay_period'],
                'start_date' => $payload['start_date'],
                'expires_at_utc' => $payload['expires_at_utc'],
                'notes' => $payload['notes'],
            ]);
            $this->offers->insertHistory($id, null, 'draft', (int) ($actor['id'] ?? 0), 'Offer drafted');
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }

        $row = $this->offers->findDetailedById($id);

        return [
            'success' => true,
            'message' => 'Offer drafted.',
            'offer' => ApiResource::jobOffer($row ?? ['id' => $id], true),
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return list<array<string, mixed>>
     */
    public function listForEmployer(array $actor, int $limit = 100): array
    {
        if ((string) ($actor['role'] ?? '') !== 'employer') {
            throw JobOfferException::forbidden();
        }
        $rows = $this->offers->listForEmployerUser((int) ($actor['id'] ?? 0), $limit);

        return array_map(
            function (array $r): array {
                $r = $this->maybeExpireRow($r, null);

                return ApiResource::jobOffer($r, false);
            },
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
            throw JobOfferException::forbidden();
        }
        $rows = $this->offers->listForCandidateUser((int) ($actor['id'] ?? 0), $limit);

        return array_map(
            function (array $r): array {
                $r = $this->maybeExpireRow($r, null);

                return ApiResource::jobOffer($r, false);
            },
            $rows
        );
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    public function getForActor(array $actor, int $offerId): array
    {
        $row = $this->offers->findDetailedById($offerId);
        if ($row === null) {
            throw JobOfferException::notFound();
        }

        $role = (string) ($actor['role'] ?? '');
        if ($role === 'employer') {
            $job = $this->jobs->findOwnedByEmployerUser((int) ($row['job_id'] ?? 0), (int) ($actor['id'] ?? 0));
            if ($job === null || !$this->policy->canManageAsEmployer($actor, $row, $job)) {
                throw JobOfferException::notFound();
            }
        } elseif ($role === 'seeker') {
            if (!$this->policy->canViewAsSeeker($actor, $row)) {
                throw JobOfferException::notFound();
            }
            // Seekers never see drafts
            if ((string) ($row['status'] ?? '') === 'draft') {
                throw JobOfferException::notFound();
            }
        } else {
            throw JobOfferException::forbidden();
        }

        $row = $this->maybeExpireRow($row, (int) ($actor['id'] ?? 0));

        return ApiResource::jobOffer($row, true);
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, offer?: array<string, mixed>}
     */
    public function send(array $actor, int $offerId, ?string $note = null): array
    {
        try {
            $row = $this->requireEmployerOffer($actor, $offerId);
        } catch (JobOfferException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
        $from = (string) ($row['status'] ?? '');
        if ($from !== 'draft') {
            return [
                'success' => false,
                'message' => JobOfferException::invalidTransition($from, 'sent')->getMessage(),
            ];
        }

        return $this->transitionEmployer($actor, $row, 'sent', 'Offer sent.', [
            'sent_at' => gmdate('Y-m-d H:i:s.v'),
        ], $note ?? 'Sent to candidate');
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, offer?: array<string, mixed>}
     */
    public function withdraw(array $actor, int $offerId, ?string $note = null): array
    {
        try {
            $row = $this->requireEmployerOffer($actor, $offerId);
        } catch (JobOfferException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
        $row = $this->maybeExpireRow($row, (int) ($actor['id'] ?? 0));
        $from = (string) ($row['status'] ?? '');
        if (!in_array($from, ['draft', 'sent'], true)) {
            return [
                'success' => false,
                'message' => JobOfferException::invalidTransition($from, 'withdrawn')->getMessage(),
            ];
        }

        return $this->transitionEmployer($actor, $row, 'withdrawn', 'Offer withdrawn.', [
            'withdrawn_at' => gmdate('Y-m-d H:i:s.v'),
        ], $note ?? 'Withdrawn by employer');
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, offer?: array<string, mixed>}
     */
    public function expire(array $actor, int $offerId, ?string $note = null): array
    {
        try {
            $row = $this->requireEmployerOffer($actor, $offerId);
        } catch (JobOfferException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
        $from = (string) ($row['status'] ?? '');
        if ($from !== 'sent') {
            return [
                'success' => false,
                'message' => JobOfferException::invalidTransition($from, 'expired')->getMessage(),
            ];
        }

        return $this->transitionEmployer($actor, $row, 'expired', 'Offer expired.', [
            'expired_at' => gmdate('Y-m-d H:i:s.v'),
        ], $note ?? 'Marked expired');
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, offer?: array<string, mixed>}
     */
    public function accept(array $actor, int $offerId): array
    {
        return $this->seekerRespond($actor, $offerId, 'accepted', 'Offer accepted.');
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, offer?: array<string, mixed>}
     */
    public function decline(array $actor, int $offerId): array
    {
        return $this->seekerRespond($actor, $offerId, 'declined', 'Offer declined.');
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, offer?: array<string, mixed>}
     */
    private function seekerRespond(array $actor, int $offerId, string $to, string $okMessage): array
    {
        $row = $this->offers->findDetailedById($offerId);
        if ($row === null || !$this->policy->canViewAsSeeker($actor, $row)) {
            return ['success' => false, 'message' => JobOfferException::notFound()->getMessage()];
        }
        if ((string) ($row['status'] ?? '') === 'draft') {
            return ['success' => false, 'message' => JobOfferException::notFound()->getMessage()];
        }

        $row = $this->maybeExpireRow($row, (int) ($actor['id'] ?? 0));
        $from = (string) ($row['status'] ?? '');
        if ($from === 'expired') {
            return [
                'success' => false,
                'message' => JobOfferException::alreadyExpired()->getMessage(),
            ];
        }
        if ($from !== 'sent') {
            return [
                'success' => false,
                'message' => JobOfferException::invalidTransition($from, $to)->getMessage(),
            ];
        }

        $extra = $to === 'accepted'
            ? ['accepted_at' => gmdate('Y-m-d H:i:s.v')]
            : ['declined_at' => gmdate('Y-m-d H:i:s.v')];

        $this->pdo->beginTransaction();
        try {
            $this->offers->updateById($offerId, array_merge(['status' => $to], $extra));
            $this->offers->insertHistory($offerId, $from, $to, (int) ($actor['id'] ?? 0), $okMessage);

            if ($to === 'accepted') {
                $appId = (int) ($row['application_id'] ?? 0);
                $app = $this->applications->findDetailedRecordById($appId);
                if ($app === null) {
                    $this->pdo->rollBack();

                    return [
                        'success' => false,
                        'message' => JobOfferException::notFound()->getMessage(),
                    ];
                }
                $appFrom = (string) ($app['status'] ?? '');
                if (!$this->hireCompletionValidator->isHireableApplicationStatus($appFrom)) {
                    $this->pdo->rollBack();

                    return [
                        'success' => false,
                        'message' => HiringCompletionException::applicationNotHireable($appFrom)->getMessage(),
                        'errors' => ['application' => [HiringCompletionException::applicationNotHireable($appFrom)->getMessage()]],
                    ];
                }
                if ($appFrom !== 'hired') {
                    $this->applications->updateApplicationStatus($appId, 'hired', null);
                    $this->applications->insertStatusHistory(
                        $appId,
                        $appFrom,
                        'hired',
                        (int) ($actor['id'] ?? 0),
                        'Hired via accepted job offer'
                    );
                    $app['status'] = 'hired';
                }

                $this->hiringCompletions->ensurePendingFromOfferAccept(
                    $app,
                    array_merge($row, ['id' => $offerId, 'status' => 'accepted']),
                    (int) ($actor['id'] ?? 0)
                );
            }

            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }

        $updated = $this->offers->findDetailedById($offerId);

        return [
            'success' => true,
            'message' => $okMessage,
            'offer' => ApiResource::jobOffer($updated ?? $row, true),
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $extraFields
     * @return array{success: bool, message: string, offer?: array<string, mixed>}
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
            $this->offers->updateById($id, $fields);
            $this->offers->insertHistory($id, $from, $to, (int) ($actor['id'] ?? 0), $note);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }

        $updated = $this->offers->findDetailedById($id);

        return [
            'success' => true,
            'message' => $okMessage,
            'offer' => ApiResource::jobOffer($updated ?? $row, true),
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    private function requireEmployerOffer(array $actor, int $offerId): array
    {
        $row = $this->offers->findDetailedById($offerId);
        if ($row === null) {
            throw JobOfferException::notFound();
        }
        $job = $this->jobs->findOwnedByEmployerUser((int) ($row['job_id'] ?? 0), (int) ($actor['id'] ?? 0));
        if ($job === null || !$this->policy->canManageAsEmployer($actor, $row, $job)) {
            throw JobOfferException::notFound();
        }

        return $row;
    }

    /**
     * Auto-expire sent offers past expires_at_utc.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function maybeExpireRow(array $row, ?int $actorUserId): array
    {
        $status = (string) ($row['status'] ?? '');
        $expires = isset($row['expires_at_utc']) ? (string) $row['expires_at_utc'] : null;
        if (!$this->validator->isExpired($expires, $status)) {
            return $row;
        }

        $id = (int) ($row['id'] ?? 0);
        if ($id < 1) {
            return $row;
        }

        try {
            $this->pdo->beginTransaction();
            $fresh = $this->offers->findById($id);
            if ($fresh === null || (string) ($fresh['status'] ?? '') !== 'sent') {
                $this->pdo->rollBack();

                return $this->offers->findDetailedById($id) ?? $row;
            }
            if (!$this->validator->isExpired(
                isset($fresh['expires_at_utc']) ? (string) $fresh['expires_at_utc'] : null,
                'sent'
            )) {
                $this->pdo->rollBack();

                return $this->offers->findDetailedById($id) ?? $row;
            }

            $this->offers->updateById($id, [
                'status' => 'expired',
                'expired_at' => gmdate('Y-m-d H:i:s.v'),
            ]);
            $this->offers->insertHistory($id, 'sent', 'expired', $actorUserId, 'Auto-expired');
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }

        return $this->offers->findDetailedById($id) ?? array_merge($row, ['status' => 'expired']);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{salary_amount: string, salary_currency: string, pay_period: string, start_date: ?string, expires_at_utc: ?string, notes: ?string}
     */
    private function normalizeCreateFields(array $input): array
    {
        $currency = strtoupper(trim((string) ($input['salary_currency'] ?? 'LKR')));
        if ($currency === '') {
            $currency = 'LKR';
        }

        $payPeriod = (string) ($input['pay_period'] ?? 'monthly');
        if (!in_array($payPeriod, JobOfferValidator::PAY_PERIODS, true)) {
            $payPeriod = 'monthly';
        }

        $startDate = null;
        if (array_key_exists('start_date', $input) && trim((string) ($input['start_date'] ?? '')) !== '') {
            $startDate = $this->validator->parseDate((string) $input['start_date']);
        }

        $expiresAtUtc = null;
        $rawExpires = trim((string) ($input['expires_at_utc'] ?? $input['expires_at'] ?? ''));
        if ($rawExpires !== '') {
            $asUtc = !empty($input['expires_at_is_utc']) || array_key_exists('expires_at_utc', $input)
                || empty($input['timezone']);
            $tz = trim((string) ($input['timezone'] ?? 'UTC'));
            $utc = $this->validator->parseToUtc($rawExpires, $asUtc, $tz !== '' ? $tz : 'UTC');
            $expiresAtUtc = $utc !== null ? $this->validator->formatUtc($utc) : null;
        }

        $notes = null;
        if (array_key_exists('notes', $input)) {
            $trimmed = trim((string) ($input['notes'] ?? ''));
            $notes = $trimmed !== '' ? mb_substr($trimmed, 0, 500) : null;
        }

        return [
            'salary_amount' => number_format((float) $input['salary_amount'], 2, '.', ''),
            'salary_currency' => $currency,
            'pay_period' => $payPeriod,
            'start_date' => $startDate,
            'expires_at_utc' => $expiresAtUtc,
            'notes' => $notes,
        ];
    }
}

<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\JobOfferRepositoryInterface;

final class JobOfferRepository extends BaseRepository implements JobOfferRepositoryInterface
{
    protected string $table = 'job_offers';

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }

        return $this->fetchOne(
            'SELECT * FROM `job_offers` WHERE `id` = :id LIMIT 1',
            ['id' => $id]
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findDetailedById(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }

        return $this->fetchOne(
            'SELECT jo.*,
                    j.`title` AS `job_title`,
                    a.`status` AS `application_status`,
                    u.`full_name` AS `candidate_name`
             FROM `job_offers` jo
             INNER JOIN `jobs` j ON j.`id` = jo.`job_id`
             INNER JOIN `applications` a ON a.`id` = jo.`application_id`
             INNER JOIN `users` u ON u.`id` = jo.`candidate_user_id`
             WHERE jo.`id` = :id
             LIMIT 1',
            ['id' => $id]
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findActiveByApplicationId(int $applicationId): ?array
    {
        if ($applicationId < 1) {
            return null;
        }

        return $this->fetchOne(
            'SELECT * FROM `job_offers`
             WHERE `application_id` = :application_id
               AND `status` IN (\'draft\', \'sent\')
             ORDER BY `id` DESC
             LIMIT 1',
            ['application_id' => $applicationId]
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForEmployerUser(int $employerUserId, int $limit = 100): array
    {
        if ($employerUserId < 1) {
            return [];
        }
        $limit = max(1, min(200, $limit));

        return $this->fetchAll(
            'SELECT jo.*,
                    j.`title` AS `job_title`,
                    a.`status` AS `application_status`,
                    u.`full_name` AS `candidate_name`
             FROM `job_offers` jo
             INNER JOIN `jobs` j ON j.`id` = jo.`job_id`
             INNER JOIN `applications` a ON a.`id` = jo.`application_id`
             INNER JOIN `users` u ON u.`id` = jo.`candidate_user_id`
             WHERE jo.`employer_user_id` = :uid
             ORDER BY jo.`created_at` DESC, jo.`id` DESC
             LIMIT ' . $limit,
            ['uid' => $employerUserId]
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForCandidateUser(int $candidateUserId, int $limit = 100): array
    {
        if ($candidateUserId < 1) {
            return [];
        }
        $limit = max(1, min(200, $limit));

        return $this->fetchAll(
            'SELECT jo.*,
                    j.`title` AS `job_title`,
                    a.`status` AS `application_status`
             FROM `job_offers` jo
             INNER JOIN `jobs` j ON j.`id` = jo.`job_id`
             INNER JOIN `applications` a ON a.`id` = jo.`application_id`
             WHERE jo.`candidate_user_id` = :uid
               AND jo.`status` <> \'draft\'
             ORDER BY jo.`created_at` DESC, jo.`id` DESC
             LIMIT ' . $limit,
            ['uid' => $candidateUserId]
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function insert(array $data): int
    {
        $this->query(
            'INSERT INTO `job_offers`
                (`application_id`, `job_id`, `employer_user_id`, `candidate_user_id`,
                 `status`, `salary_amount`, `salary_currency`, `pay_period`,
                 `start_date`, `expires_at_utc`, `notes`)
             VALUES
                (:application_id, :job_id, :employer_user_id, :candidate_user_id,
                 :status, :salary_amount, :salary_currency, :pay_period,
                 :start_date, :expires_at_utc, :notes)',
            [
                'application_id' => (int) ($data['application_id'] ?? 0),
                'job_id' => (int) ($data['job_id'] ?? 0),
                'employer_user_id' => (int) ($data['employer_user_id'] ?? 0),
                'candidate_user_id' => (int) ($data['candidate_user_id'] ?? 0),
                'status' => (string) ($data['status'] ?? 'draft'),
                'salary_amount' => (string) ($data['salary_amount'] ?? '0'),
                'salary_currency' => (string) ($data['salary_currency'] ?? 'LKR'),
                'pay_period' => (string) ($data['pay_period'] ?? 'monthly'),
                'start_date' => $data['start_date'] ?? null,
                'expires_at_utc' => $data['expires_at_utc'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]
        );

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    public function updateById(int $id, array $fields): bool
    {
        if ($id < 1 || $fields === []) {
            return false;
        }
        $allowed = [
            'status', 'salary_amount', 'salary_currency', 'pay_period',
            'start_date', 'expires_at_utc', 'notes',
            'sent_at', 'accepted_at', 'declined_at', 'withdrawn_at', 'expired_at',
        ];
        $sets = [];
        $params = ['id' => $id];
        foreach ($allowed as $col) {
            if (!array_key_exists($col, $fields)) {
                continue;
            }
            $sets[] = '`' . $col . '` = :' . $col;
            $params[$col] = $fields[$col];
        }
        if ($sets === []) {
            return false;
        }
        $this->query(
            'UPDATE `job_offers` SET ' . implode(', ', $sets) . ' WHERE `id` = :id',
            $params
        );

        return true;
    }

    public function insertHistory(
        int $offerId,
        ?string $fromStatus,
        string $toStatus,
        ?int $actorUserId,
        ?string $note = null
    ): void {
        if ($offerId < 1 || $toStatus === '') {
            return;
        }
        $this->query(
            'INSERT INTO `job_offer_history`
                (`offer_id`, `from_status`, `to_status`, `actor_user_id`, `note`)
             VALUES
                (:offer_id, :from_status, :to_status, :actor_user_id, :note)',
            [
                'offer_id' => $offerId,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'actor_user_id' => $actorUserId !== null && $actorUserId > 0 ? $actorUserId : null,
                'note' => $note !== null ? mb_substr($note, 0, 500) : null,
            ]
        );
    }
}

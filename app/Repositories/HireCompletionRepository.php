<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\HireCompletionRepositoryInterface;

final class HireCompletionRepository extends BaseRepository implements HireCompletionRepositoryInterface
{
    protected string $table = 'hire_completions';

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }

        return $this->fetchOne(
            'SELECT * FROM `hire_completions` WHERE `id` = :id LIMIT 1',
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
            'SELECT hc.*,
                    j.`title` AS `job_title`,
                    j.`vacancies` AS `job_vacancies`,
                    j.`status` AS `job_status`,
                    a.`status` AS `application_status`,
                    u.`full_name` AS `candidate_name`
             FROM `hire_completions` hc
             INNER JOIN `jobs` j ON j.`id` = hc.`job_id`
             INNER JOIN `applications` a ON a.`id` = hc.`application_id`
             INNER JOIN `users` u ON u.`id` = hc.`candidate_user_id`
             WHERE hc.`id` = :id
             LIMIT 1',
            ['id' => $id]
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByApplicationId(int $applicationId): ?array
    {
        if ($applicationId < 1) {
            return null;
        }

        return $this->fetchOne(
            'SELECT * FROM `hire_completions` WHERE `application_id` = :application_id LIMIT 1',
            ['application_id' => $applicationId]
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByOfferId(int $offerId): ?array
    {
        if ($offerId < 1) {
            return null;
        }

        return $this->fetchOne(
            'SELECT * FROM `hire_completions` WHERE `offer_id` = :offer_id LIMIT 1',
            ['offer_id' => $offerId]
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
            'SELECT hc.*,
                    j.`title` AS `job_title`,
                    a.`status` AS `application_status`,
                    u.`full_name` AS `candidate_name`
             FROM `hire_completions` hc
             INNER JOIN `jobs` j ON j.`id` = hc.`job_id`
             INNER JOIN `applications` a ON a.`id` = hc.`application_id`
             INNER JOIN `users` u ON u.`id` = hc.`candidate_user_id`
             WHERE hc.`employer_user_id` = :uid
             ORDER BY hc.`created_at` DESC, hc.`id` DESC
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
            'SELECT hc.*,
                    j.`title` AS `job_title`,
                    a.`status` AS `application_status`
             FROM `hire_completions` hc
             INNER JOIN `jobs` j ON j.`id` = hc.`job_id`
             INNER JOIN `applications` a ON a.`id` = hc.`application_id`
             WHERE hc.`candidate_user_id` = :uid
             ORDER BY hc.`created_at` DESC, hc.`id` DESC
             LIMIT ' . $limit,
            ['uid' => $candidateUserId]
        );
    }

    public function countCompletedByJobId(int $jobId): int
    {
        if ($jobId < 1) {
            return 0;
        }
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS `cnt` FROM `hire_completions`
             WHERE `job_id` = :job_id AND `status` = \'completed\'',
            ['job_id' => $jobId]
        );

        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function insert(array $data): int
    {
        $this->query(
            'INSERT INTO `hire_completions`
                (`application_id`, `job_id`, `employer_user_id`, `candidate_user_id`,
                 `offer_id`, `status`, `start_date`, `notes`)
             VALUES
                (:application_id, :job_id, :employer_user_id, :candidate_user_id,
                 :offer_id, :status, :start_date, :notes)',
            [
                'application_id' => (int) ($data['application_id'] ?? 0),
                'job_id' => (int) ($data['job_id'] ?? 0),
                'employer_user_id' => (int) ($data['employer_user_id'] ?? 0),
                'candidate_user_id' => (int) ($data['candidate_user_id'] ?? 0),
                'offer_id' => isset($data['offer_id']) && (int) $data['offer_id'] > 0
                    ? (int) $data['offer_id']
                    : null,
                'status' => (string) ($data['status'] ?? 'pending'),
                'start_date' => $data['start_date'] ?? null,
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
            'status', 'start_date', 'notes', 'offer_id',
            'confirmed_at', 'completed_at', 'cancelled_at',
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
            'UPDATE `hire_completions` SET ' . implode(', ', $sets) . ' WHERE `id` = :id',
            $params
        );

        return true;
    }

    public function insertHistory(
        int $hireCompletionId,
        ?string $fromStatus,
        string $toStatus,
        ?int $actorUserId,
        ?string $note = null
    ): void {
        if ($hireCompletionId < 1 || $toStatus === '') {
            return;
        }
        $this->query(
            'INSERT INTO `hire_completion_history`
                (`hire_completion_id`, `from_status`, `to_status`, `actor_user_id`, `note`)
             VALUES
                (:hire_completion_id, :from_status, :to_status, :actor_user_id, :note)',
            [
                'hire_completion_id' => $hireCompletionId,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'actor_user_id' => $actorUserId !== null && $actorUserId > 0 ? $actorUserId : null,
                'note' => $note !== null ? mb_substr($note, 0, 500) : null,
            ]
        );
    }
}

<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\ScheduledInterviewRepositoryInterface;

final class ScheduledInterviewRepository extends BaseRepository implements ScheduledInterviewRepositoryInterface
{
    protected string $table = 'scheduled_interviews';

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }

        return $this->fetchOne(
            'SELECT * FROM `scheduled_interviews` WHERE `id` = :id LIMIT 1',
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
            'SELECT si.*,
                    j.`title` AS `job_title`,
                    a.`status` AS `application_status`,
                    u.`full_name` AS `candidate_name`
             FROM `scheduled_interviews` si
             INNER JOIN `jobs` j ON j.`id` = si.`job_id`
             INNER JOIN `applications` a ON a.`id` = si.`application_id`
             INNER JOIN `users` u ON u.`id` = si.`candidate_user_id`
             WHERE si.`id` = :id
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
            'SELECT * FROM `scheduled_interviews`
             WHERE `application_id` = :application_id
               AND `status` IN (\'proposed\', \'confirmed\')
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
            'SELECT si.*,
                    j.`title` AS `job_title`,
                    a.`status` AS `application_status`,
                    u.`full_name` AS `candidate_name`
             FROM `scheduled_interviews` si
             INNER JOIN `jobs` j ON j.`id` = si.`job_id`
             INNER JOIN `applications` a ON a.`id` = si.`application_id`
             INNER JOIN `users` u ON u.`id` = si.`candidate_user_id`
             WHERE si.`employer_user_id` = :uid
             ORDER BY si.`scheduled_at_utc` ASC, si.`id` DESC
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
            'SELECT si.*,
                    j.`title` AS `job_title`,
                    a.`status` AS `application_status`
             FROM `scheduled_interviews` si
             INNER JOIN `jobs` j ON j.`id` = si.`job_id`
             INNER JOIN `applications` a ON a.`id` = si.`application_id`
             WHERE si.`candidate_user_id` = :uid
             ORDER BY si.`scheduled_at_utc` ASC, si.`id` DESC
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
            'INSERT INTO `scheduled_interviews`
                (`application_id`, `job_id`, `employer_user_id`, `candidate_user_id`,
                 `status`, `scheduled_at_utc`, `duration_minutes`, `timezone`,
                 `location_type`, `location_notes`, `round_number`)
             VALUES
                (:application_id, :job_id, :employer_user_id, :candidate_user_id,
                 :status, :scheduled_at_utc, :duration_minutes, :timezone,
                 :location_type, :location_notes, :round_number)',
            [
                'application_id' => (int) ($data['application_id'] ?? 0),
                'job_id' => (int) ($data['job_id'] ?? 0),
                'employer_user_id' => (int) ($data['employer_user_id'] ?? 0),
                'candidate_user_id' => (int) ($data['candidate_user_id'] ?? 0),
                'status' => (string) ($data['status'] ?? 'proposed'),
                'scheduled_at_utc' => (string) ($data['scheduled_at_utc'] ?? ''),
                'duration_minutes' => (int) ($data['duration_minutes'] ?? 60),
                'timezone' => (string) ($data['timezone'] ?? 'UTC'),
                'location_type' => (string) ($data['location_type'] ?? 'other'),
                'location_notes' => $data['location_notes'] ?? null,
                'round_number' => (int) ($data['round_number'] ?? 1),
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
            'status', 'scheduled_at_utc', 'duration_minutes', 'timezone',
            'location_type', 'location_notes', 'round_number',
            'cancelled_at', 'completed_at',
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
            'UPDATE `scheduled_interviews` SET ' . implode(', ', $sets) . ' WHERE `id` = :id',
            $params
        );

        return true;
    }

    public function insertHistory(
        int $interviewId,
        ?string $fromStatus,
        string $toStatus,
        ?int $actorUserId,
        ?string $note = null
    ): void {
        if ($interviewId < 1 || $toStatus === '') {
            return;
        }
        $this->query(
            'INSERT INTO `scheduled_interview_history`
                (`interview_id`, `from_status`, `to_status`, `actor_user_id`, `note`)
             VALUES
                (:interview_id, :from_status, :to_status, :actor_user_id, :note)',
            [
                'interview_id' => $interviewId,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'actor_user_id' => $actorUserId !== null && $actorUserId > 0 ? $actorUserId : null,
                'note' => $note !== null ? mb_substr($note, 0, 500) : null,
            ]
        );
    }

    public function cancelActiveByApplicationId(int $applicationId, ?int $actorUserId, ?string $note = null): int
    {
        if ($applicationId < 1) {
            return 0;
        }
        $rows = $this->fetchAll(
            'SELECT `id`, `status` FROM `scheduled_interviews`
             WHERE `application_id` = :application_id
               AND `status` IN (\'proposed\', \'confirmed\')',
            ['application_id' => $applicationId]
        );
        if ($rows === []) {
            return 0;
        }
        $cancelledAt = gmdate('Y-m-d H:i:s.v');
        $count = 0;
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            $from = (string) ($row['status'] ?? '');
            if ($id < 1) {
                continue;
            }
            $this->updateById($id, [
                'status' => 'cancelled',
                'cancelled_at' => $cancelledAt,
            ]);
            $this->insertHistory(
                $id,
                $from,
                'cancelled',
                $actorUserId,
                $note ?? 'Cancelled due to hire completion'
            );
            $count++;
        }

        return $count;
    }
}

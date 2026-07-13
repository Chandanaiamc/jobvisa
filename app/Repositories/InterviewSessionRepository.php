<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\InterviewSessionRepositoryInterface;

final class InterviewSessionRepository extends BaseRepository implements InterviewSessionRepositoryInterface
{
    protected string $table = 'interview_sessions';

    public function create(array $payload): int
    {
        $technical = $this->encodeJson($payload['technical_questions'] ?? []);
        $behavioral = $this->encodeJson($payload['behavioral_questions'] ?? []);
        $strengths = $this->encodeJson($payload['strengths_json'] ?? []);
        $weaknesses = $this->encodeJson($payload['weaknesses_json'] ?? []);
        $recommendations = $this->encodeJson($payload['recommendations_json'] ?? []);
        $context = $this->encodeJson($payload['context_scores_json'] ?? []);

        $this->query(
            'INSERT INTO `interview_sessions`
                (`employer_user_id`, `job_id`, `application_id`, `resume_id`, `candidate_user_id`, `status`,
                 `technical_questions`, `behavioral_questions`, `strengths_json`, `weaknesses_json`,
                 `recommendations_json`, `context_scores_json`, `assistant_version`)
             VALUES
                (:employer_user_id, :job_id, :application_id, :resume_id, :candidate_user_id, :status,
                 :technical_questions, :behavioral_questions, :strengths_json, :weaknesses_json,
                 :recommendations_json, :context_scores_json, :assistant_version)',
            [
                'employer_user_id' => (int) ($payload['employer_user_id'] ?? 0),
                'job_id' => (int) ($payload['job_id'] ?? 0),
                'application_id' => (int) ($payload['application_id'] ?? 0),
                'resume_id' => isset($payload['resume_id']) && $payload['resume_id'] !== null
                    ? (int) $payload['resume_id']
                    : null,
                'candidate_user_id' => (int) ($payload['candidate_user_id'] ?? 0),
                'status' => (string) ($payload['status'] ?? 'prepared'),
                'technical_questions' => $technical,
                'behavioral_questions' => $behavioral,
                'strengths_json' => $strengths,
                'weaknesses_json' => $weaknesses,
                'recommendations_json' => $recommendations,
                'context_scores_json' => $context,
                'assistant_version' => (string) ($payload['assistant_version'] ?? ''),
            ]
        );

        return (int) $this->pdo->lastInsertId();
    }

    public function findOwned(int $sessionId, int $employerUserId): ?array
    {
        if ($sessionId < 1 || $employerUserId < 1) {
            return null;
        }

        return $this->fetchOne(
            'SELECT s.*, j.`title` AS `job_title`,
                    u.`full_name` AS `candidate_name`, u.`email` AS `candidate_email`
             FROM `interview_sessions` s
             INNER JOIN `jobs` j ON j.`id` = s.`job_id`
             INNER JOIN `users` u ON u.`id` = s.`candidate_user_id`
             WHERE s.`id` = :id
               AND s.`employer_user_id` = :uid
               AND s.`deleted_at` IS NULL
             LIMIT 1',
            ['id' => $sessionId, 'uid' => $employerUserId]
        );
    }

    public function listByEmployer(int $employerUserId, int $limit = 25): array
    {
        if ($employerUserId < 1) {
            return [];
        }
        $limit = max(1, min(100, $limit));

        return $this->fetchAll(
            'SELECT s.`id`, s.`job_id`, s.`application_id`, s.`resume_id`, s.`candidate_user_id`,
                    s.`status`, s.`assistant_version`, s.`created_at`,
                    j.`title` AS `job_title`,
                    u.`full_name` AS `candidate_name`, u.`email` AS `candidate_email`,
                    sc.`overall_score` AS `scorecard_overall`,
                    sc.`hiring_recommendation`
             FROM `interview_sessions` s
             INNER JOIN `jobs` j ON j.`id` = s.`job_id`
             INNER JOIN `users` u ON u.`id` = s.`candidate_user_id`
             LEFT JOIN `interview_scorecards` sc ON sc.`session_id` = s.`id`
             WHERE s.`employer_user_id` = :uid AND s.`deleted_at` IS NULL
             ORDER BY s.`created_at` DESC, s.`id` DESC
             LIMIT ' . $limit,
            ['uid' => $employerUserId]
        );
    }

    public function listCandidatesForJob(int $jobId, int $limit = 100): array
    {
        if ($jobId < 1) {
            return [];
        }
        $limit = max(1, min(300, $limit));

        return $this->fetchAll(
            'SELECT a.`id` AS `application_id`, a.`job_id`, a.`user_id` AS `candidate_user_id`,
                    a.`resume_id`, a.`status` AS `application_status`, a.`applied_at`,
                    u.`full_name` AS `candidate_name`, u.`email` AS `candidate_email`,
                    r.`title` AS `resume_title`,
                    jar.`overall_score` AS `ranking_score`, jar.`rank_position`,
                    jar.`job_match_score`, jar.`resume_score`,
                    rjm.`overall_score` AS `match_score`
             FROM `applications` a
             INNER JOIN `users` u ON u.`id` = a.`user_id`
             LEFT JOIN `resumes` r ON r.`id` = a.`resume_id` AND r.`deleted_at` IS NULL
             LEFT JOIN `job_applicant_rankings` jar
                    ON jar.`application_id` = a.`id` AND jar.`job_id` = a.`job_id` AND jar.`deleted_at` IS NULL
             LEFT JOIN `resume_job_match_snapshots` rjm
                    ON rjm.`resume_id` = a.`resume_id` AND rjm.`job_id` = a.`job_id` AND rjm.`deleted_at` IS NULL
             WHERE a.`job_id` = :job_id
             ORDER BY COALESCE(jar.`rank_position`, 9999) ASC, a.`applied_at` DESC
             LIMIT ' . $limit,
            ['job_id' => $jobId]
        );
    }

    public function updateStatus(int $sessionId, int $employerUserId, string $status): bool
    {
        $stmt = $this->query(
            'UPDATE `interview_sessions`
             SET `status` = :status
             WHERE `id` = :id AND `employer_user_id` = :uid AND `deleted_at` IS NULL',
            [
                'status' => mb_substr($status, 0, 32),
                'id' => $sessionId,
                'uid' => $employerUserId,
            ]
        );

        return $stmt->rowCount() > 0;
    }

    public function softDelete(int $sessionId, int $employerUserId): bool
    {
        $stmt = $this->query(
            'UPDATE `interview_sessions`
             SET `deleted_at` = CURRENT_TIMESTAMP(3)
             WHERE `id` = :id AND `employer_user_id` = :uid AND `deleted_at` IS NULL',
            ['id' => $sessionId, 'uid' => $employerUserId]
        );

        return $stmt->rowCount() > 0;
    }

    public function softDeleteAllForEmployer(int $employerUserId): int
    {
        if ($employerUserId < 1) {
            return 0;
        }
        $stmt = $this->query(
            'UPDATE `interview_sessions`
             SET `deleted_at` = CURRENT_TIMESTAMP(3)
             WHERE `employer_user_id` = :uid AND `deleted_at` IS NULL',
            ['uid' => $employerUserId]
        );

        return $stmt->rowCount();
    }

    /**
     * @param  array<mixed>|list<mixed>  $data
     */
    private function encodeJson(array $data): string
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new \RuntimeException('Unable to encode interview session JSON.');
        }

        return $json;
    }
}

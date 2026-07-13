<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\JobApplicantRankingRepositoryInterface;

final class JobApplicantRankingRepository extends BaseRepository implements JobApplicantRankingRepositoryInterface
{
    protected string $table = 'job_applicant_rankings';

    public function upsert(int $jobId, int $applicationId, array $payload): void
    {
        $breakdown = json_encode($payload['score_breakdown'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $explanation = json_encode($payload['explanation_json'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($breakdown === false || $explanation === false) {
            throw new \RuntimeException('Unable to encode applicant ranking JSON.');
        }

        $this->query(
            'INSERT INTO `job_applicant_rankings`
                (`job_id`, `application_id`, `resume_id`, `applicant_user_id`, `rank_position`,
                 `overall_score`, `resume_score`, `job_match_score`, `skills_score`, `experience_score`,
                 `education_score`, `certification_score`, `portfolio_score`, `references_score`,
                 `score_breakdown`, `explanation_json`, `rules_version`, `calculated_at`, `deleted_at`)
             VALUES
                (:job_id, :application_id, :resume_id, :applicant_user_id, :rank_position,
                 :overall_score, :resume_score, :job_match_score, :skills_score, :experience_score,
                 :education_score, :certification_score, :portfolio_score, :references_score,
                 :score_breakdown, :explanation_json, :rules_version, :calculated_at, NULL)
             ON DUPLICATE KEY UPDATE
                `resume_id` = VALUES(`resume_id`),
                `applicant_user_id` = VALUES(`applicant_user_id`),
                `rank_position` = VALUES(`rank_position`),
                `overall_score` = VALUES(`overall_score`),
                `resume_score` = VALUES(`resume_score`),
                `job_match_score` = VALUES(`job_match_score`),
                `skills_score` = VALUES(`skills_score`),
                `experience_score` = VALUES(`experience_score`),
                `education_score` = VALUES(`education_score`),
                `certification_score` = VALUES(`certification_score`),
                `portfolio_score` = VALUES(`portfolio_score`),
                `references_score` = VALUES(`references_score`),
                `score_breakdown` = VALUES(`score_breakdown`),
                `explanation_json` = VALUES(`explanation_json`),
                `rules_version` = VALUES(`rules_version`),
                `calculated_at` = VALUES(`calculated_at`),
                `deleted_at` = NULL',
            [
                'job_id' => $jobId,
                'application_id' => $applicationId,
                'resume_id' => $payload['resume_id'] !== null ? (int) $payload['resume_id'] : null,
                'applicant_user_id' => (int) ($payload['applicant_user_id'] ?? 0),
                'rank_position' => max(0, (int) ($payload['rank_position'] ?? 0)),
                'overall_score' => max(0, min(100, (int) ($payload['overall_score'] ?? 0))),
                'resume_score' => max(0, min(100, (int) ($payload['resume_score'] ?? 0))),
                'job_match_score' => max(0, min(100, (int) ($payload['job_match_score'] ?? 0))),
                'skills_score' => max(0, min(100, (int) ($payload['skills_score'] ?? 0))),
                'experience_score' => max(0, min(100, (int) ($payload['experience_score'] ?? 0))),
                'education_score' => max(0, min(100, (int) ($payload['education_score'] ?? 0))),
                'certification_score' => max(0, min(100, (int) ($payload['certification_score'] ?? 0))),
                'portfolio_score' => max(0, min(100, (int) ($payload['portfolio_score'] ?? 0))),
                'references_score' => max(0, min(100, (int) ($payload['references_score'] ?? 0))),
                'score_breakdown' => $breakdown,
                'explanation_json' => $explanation,
                'rules_version' => (string) ($payload['rules_version'] ?? ''),
                'calculated_at' => (string) ($payload['calculated_at'] ?? date('Y-m-d H:i:s')),
            ]
        );
    }

    public function softDeleteAllForJob(int $jobId): int
    {
        if ($jobId < 1) {
            return 0;
        }
        $stmt = $this->query(
            'UPDATE `job_applicant_rankings`
             SET `deleted_at` = CURRENT_TIMESTAMP(3)
             WHERE `job_id` = :job_id AND `deleted_at` IS NULL',
            ['job_id' => $jobId]
        );

        return $stmt->rowCount();
    }

    public function listByJobId(int $jobId, int $limit = 200): array
    {
        if ($jobId < 1) {
            return [];
        }
        $limit = max(1, min(500, $limit));

        return $this->fetchAll(
            'SELECT r.*, a.`status` AS `application_status`, a.`applied_at`,
                    u.`full_name` AS `applicant_name`, u.`email` AS `applicant_email`
             FROM `job_applicant_rankings` r
             INNER JOIN `applications` a ON a.`id` = r.`application_id`
             INNER JOIN `users` u ON u.`id` = r.`applicant_user_id`
             WHERE r.`job_id` = :job_id AND r.`deleted_at` IS NULL
             ORDER BY r.`rank_position` ASC, r.`overall_score` DESC
             LIMIT ' . $limit,
            ['job_id' => $jobId]
        );
    }

    public function listByJobIds(array $jobIds, int $limit = 500): array
    {
        $ids = [];
        foreach ($jobIds as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }
        if ($ids === []) {
            return [];
        }

        $limit = max(1, min(1000, $limit));
        $placeholders = [];
        $params = [];
        $i = 0;
        foreach ($ids as $id) {
            $key = 'j' . $i;
            $placeholders[] = ':' . $key;
            $params[$key] = $id;
            $i++;
        }

        return $this->fetchAll(
            'SELECT r.*, a.`status` AS `application_status`, a.`applied_at`,
                    u.`full_name` AS `applicant_name`, u.`email` AS `applicant_email`,
                    j.`title` AS `job_title`
             FROM `job_applicant_rankings` r
             INNER JOIN `applications` a ON a.`id` = r.`application_id`
             INNER JOIN `users` u ON u.`id` = r.`applicant_user_id`
             INNER JOIN `jobs` j ON j.`id` = r.`job_id`
             WHERE r.`deleted_at` IS NULL
               AND r.`job_id` IN (' . implode(', ', $placeholders) . ')
             ORDER BY r.`overall_score` DESC, r.`rank_position` ASC
             LIMIT ' . $limit,
            $params
        );
    }

    public function appendHistory(int $jobId, int $applicationId, array $payload): int
    {
        $breakdown = json_encode($payload['score_breakdown'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $explanation = json_encode($payload['explanation_json'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($breakdown === false || $explanation === false) {
            throw new \RuntimeException('Unable to encode ranking history JSON.');
        }

        $this->query(
            'INSERT INTO `job_applicant_ranking_history`
                (`job_id`, `application_id`, `resume_id`, `applicant_user_id`, `rank_position`,
                 `overall_score`, `resume_score`, `job_match_score`, `skills_score`, `experience_score`,
                 `education_score`, `certification_score`, `portfolio_score`, `references_score`,
                 `score_breakdown`, `explanation_json`, `application_status`, `rules_version`, `calculated_at`)
             VALUES
                (:job_id, :application_id, :resume_id, :applicant_user_id, :rank_position,
                 :overall_score, :resume_score, :job_match_score, :skills_score, :experience_score,
                 :education_score, :certification_score, :portfolio_score, :references_score,
                 :score_breakdown, :explanation_json, :application_status, :rules_version, :calculated_at)',
            [
                'job_id' => $jobId,
                'application_id' => $applicationId,
                'resume_id' => $payload['resume_id'] !== null ? (int) $payload['resume_id'] : null,
                'applicant_user_id' => (int) ($payload['applicant_user_id'] ?? 0),
                'rank_position' => max(0, (int) ($payload['rank_position'] ?? 0)),
                'overall_score' => max(0, min(100, (int) ($payload['overall_score'] ?? 0))),
                'resume_score' => max(0, min(100, (int) ($payload['resume_score'] ?? 0))),
                'job_match_score' => max(0, min(100, (int) ($payload['job_match_score'] ?? 0))),
                'skills_score' => max(0, min(100, (int) ($payload['skills_score'] ?? 0))),
                'experience_score' => max(0, min(100, (int) ($payload['experience_score'] ?? 0))),
                'education_score' => max(0, min(100, (int) ($payload['education_score'] ?? 0))),
                'certification_score' => max(0, min(100, (int) ($payload['certification_score'] ?? 0))),
                'portfolio_score' => max(0, min(100, (int) ($payload['portfolio_score'] ?? 0))),
                'references_score' => max(0, min(100, (int) ($payload['references_score'] ?? 0))),
                'score_breakdown' => $breakdown,
                'explanation_json' => $explanation,
                'application_status' => $payload['application_status'] !== null
                    ? (string) $payload['application_status']
                    : null,
                'rules_version' => (string) ($payload['rules_version'] ?? ''),
                'calculated_at' => (string) ($payload['calculated_at'] ?? date('Y-m-d H:i:s')),
            ]
        );

        return (int) $this->pdo->lastInsertId();
    }

    public function listHistoryByJobId(int $jobId, int $limit = 100): array
    {
        if ($jobId < 1) {
            return [];
        }
        $limit = max(1, min(300, $limit));

        return $this->fetchAll(
            'SELECT h.*, u.`full_name` AS `applicant_name`, u.`email` AS `applicant_email`
             FROM `job_applicant_ranking_history` h
             LEFT JOIN `users` u ON u.`id` = h.`applicant_user_id`
             WHERE h.`job_id` = :job_id AND h.`deleted_at` IS NULL
             ORDER BY h.`calculated_at` DESC, h.`rank_position` ASC, h.`id` DESC
             LIMIT ' . $limit,
            ['job_id' => $jobId]
        );
    }

    public function softDeleteHistory(int $historyId, int $jobId): bool
    {
        $stmt = $this->query(
            'UPDATE `job_applicant_ranking_history`
             SET `deleted_at` = CURRENT_TIMESTAMP(3)
             WHERE `id` = :id AND `job_id` = :job_id AND `deleted_at` IS NULL',
            ['id' => $historyId, 'job_id' => $jobId]
        );

        return $stmt->rowCount() > 0;
    }

    public function softDeleteAllHistoryForJob(int $jobId): int
    {
        if ($jobId < 1) {
            return 0;
        }
        $stmt = $this->query(
            'UPDATE `job_applicant_ranking_history`
             SET `deleted_at` = CURRENT_TIMESTAMP(3)
             WHERE `job_id` = :job_id AND `deleted_at` IS NULL',
            ['job_id' => $jobId]
        );

        return $stmt->rowCount();
    }
}

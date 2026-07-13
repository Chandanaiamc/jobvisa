<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\ResumeJobMatchRepositoryInterface;

final class ResumeJobMatchRepository extends BaseRepository implements ResumeJobMatchRepositoryInterface
{
    protected string $table = 'resume_job_match_snapshots';

    public function findByResumeAndJob(int $resumeId, int $jobId): ?array
    {
        if ($resumeId < 1 || $jobId < 1) {
            return null;
        }

        return $this->fetchOne(
            'SELECT * FROM `resume_job_match_snapshots`
             WHERE `resume_id` = :resume_id AND `job_id` = :job_id AND `deleted_at` IS NULL
             LIMIT 1',
            ['resume_id' => $resumeId, 'job_id' => $jobId]
        );
    }

    public function upsert(int $resumeId, int $jobId, array $payload): void
    {
        $breakdown = json_encode($payload['score_breakdown'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $explanation = json_encode($payload['explanation_json'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $recs = json_encode($payload['recommendations'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($breakdown === false || $explanation === false || $recs === false) {
            throw new \RuntimeException('Unable to encode job match snapshot JSON.');
        }

        $this->query(
            'INSERT INTO `resume_job_match_snapshots`
                (`resume_id`, `job_id`, `overall_score`, `skills_score`, `experience_score`, `education_score`,
                 `language_score`, `certification_score`, `location_score`, `score_breakdown`, `explanation_json`,
                 `recommendations`, `rules_version`, `calculated_at`, `deleted_at`)
             VALUES
                (:resume_id, :job_id, :overall_score, :skills_score, :experience_score, :education_score,
                 :language_score, :certification_score, :location_score, :score_breakdown, :explanation_json,
                 :recommendations, :rules_version, :calculated_at, NULL)
             ON DUPLICATE KEY UPDATE
                `overall_score` = VALUES(`overall_score`),
                `skills_score` = VALUES(`skills_score`),
                `experience_score` = VALUES(`experience_score`),
                `education_score` = VALUES(`education_score`),
                `language_score` = VALUES(`language_score`),
                `certification_score` = VALUES(`certification_score`),
                `location_score` = VALUES(`location_score`),
                `score_breakdown` = VALUES(`score_breakdown`),
                `explanation_json` = VALUES(`explanation_json`),
                `recommendations` = VALUES(`recommendations`),
                `rules_version` = VALUES(`rules_version`),
                `calculated_at` = VALUES(`calculated_at`),
                `deleted_at` = NULL',
            [
                'resume_id' => $resumeId,
                'job_id' => $jobId,
                'overall_score' => max(0, min(100, (int) ($payload['overall_score'] ?? 0))),
                'skills_score' => max(0, min(100, (int) ($payload['skills_score'] ?? 0))),
                'experience_score' => max(0, min(100, (int) ($payload['experience_score'] ?? 0))),
                'education_score' => max(0, min(100, (int) ($payload['education_score'] ?? 0))),
                'language_score' => max(0, min(100, (int) ($payload['language_score'] ?? 0))),
                'certification_score' => max(0, min(100, (int) ($payload['certification_score'] ?? 0))),
                'location_score' => max(0, min(100, (int) ($payload['location_score'] ?? 0))),
                'score_breakdown' => $breakdown,
                'explanation_json' => $explanation,
                'recommendations' => $recs,
                'rules_version' => (string) ($payload['rules_version'] ?? ''),
                'calculated_at' => (string) ($payload['calculated_at'] ?? date('Y-m-d H:i:s')),
            ]
        );
    }

    public function listTopForResume(int $resumeId, int $limit = 20): array
    {
        if ($resumeId < 1) {
            return [];
        }
        $limit = max(1, min(100, $limit));

        return $this->fetchAll(
            'SELECT m.*, j.`title` AS job_title, j.`slug` AS job_slug, j.`status` AS job_status,
                    j.`country_id`, c.`name` AS country_name, j.`experience_min_years`
             FROM `resume_job_match_snapshots` m
             INNER JOIN `jobs` j ON j.`id` = m.`job_id`
             LEFT JOIN `countries` c ON c.`id` = j.`country_id`
             WHERE m.`resume_id` = :resume_id
               AND m.`deleted_at` IS NULL
               AND j.`status` = \'published\'
             ORDER BY m.`overall_score` DESC, m.`calculated_at` DESC
             LIMIT ' . $limit,
            ['resume_id' => $resumeId]
        );
    }

    public function listActiveByJobIds(array $jobIds, int $limit = 500): array
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
            'SELECT m.`job_id`, m.`resume_id`, m.`overall_score`, m.`skills_score`, m.`explanation_json`,
                    j.`title` AS job_title
             FROM `resume_job_match_snapshots` m
             INNER JOIN `jobs` j ON j.`id` = m.`job_id`
             WHERE m.`deleted_at` IS NULL
               AND m.`job_id` IN (' . implode(', ', $placeholders) . ')
             ORDER BY m.`overall_score` DESC
             LIMIT ' . $limit,
            $params
        );
    }

    public function softDelete(int $resumeId, int $jobId): bool
    {
        $stmt = $this->query(
            'UPDATE `resume_job_match_snapshots`
             SET `deleted_at` = CURRENT_TIMESTAMP(3)
             WHERE `resume_id` = :resume_id AND `job_id` = :job_id AND `deleted_at` IS NULL',
            ['resume_id' => $resumeId, 'job_id' => $jobId]
        );

        return $stmt->rowCount() > 0;
    }
}

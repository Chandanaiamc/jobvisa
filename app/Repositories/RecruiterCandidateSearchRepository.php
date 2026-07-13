<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\RecruiterCandidateSearchRepositoryInterface;

/**
 * SQL candidate search for recruiter assistant (owned jobs only via job id list).
 */
final class RecruiterCandidateSearchRepository extends BaseRepository implements RecruiterCandidateSearchRepositoryInterface
{
    protected string $table = 'applications';

    public function search(array $jobIds, array $filters, int $limit = 25): array
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

        $limit = max(1, min(100, $limit));
        $placeholders = [];
        $params = [];
        $i = 0;
        foreach ($ids as $id) {
            $key = 'j' . $i;
            $placeholders[] = ':' . $key;
            $params[$key] = $id;
            $i++;
        }
        $inJobs = implode(', ', $placeholders);

        $where = [
            'a.`job_id` IN (' . $inJobs . ')',
            'a.`status` NOT IN (\'withdrawn\')',
        ];

        if (!empty($filters['job_id'])) {
            $where[] = 'a.`job_id` = :filter_job_id';
            $params['filter_job_id'] = (int) $filters['job_id'];
        }

        if (isset($filters['min_match_score']) && $filters['min_match_score'] !== null) {
            $where[] = 'COALESCE(r.`job_match_score`, m.`overall_score`, 0) >= :min_match';
            $params['min_match'] = (int) $filters['min_match_score'];
        }

        if (isset($filters['min_ranking_score']) && $filters['min_ranking_score'] !== null) {
            $where[] = 'COALESCE(r.`overall_score`, 0) >= :min_rank';
            $params['min_rank'] = (int) $filters['min_ranking_score'];
        }

        if (isset($filters['min_experience_years']) && $filters['min_experience_years'] !== null) {
            $where[] = 'COALESCE(rp.`years_of_experience`, 0) >= :min_years';
            $params['min_years'] = (int) $filters['min_experience_years'];
        }

        if (!empty($filters['location'])) {
            $where[] = '(
                LOWER(COALESCE(c.`name`, \'\')) LIKE :loc1
                OR LOWER(COALESCE(j.`title`, \'\')) LIKE :loc2
                OR EXISTS (
                    SELECT 1 FROM `resume_preferred_countries` rpc
                    INNER JOIN `countries` pc ON pc.`id` = rpc.`country_id`
                    WHERE rpc.`resume_id` = a.`resume_id` AND LOWER(pc.`name`) LIKE :loc3
                )
            )';
            $like = '%' . mb_strtolower((string) $filters['location']) . '%';
            $params['loc1'] = $like;
            $params['loc2'] = $like;
            $params['loc3'] = $like;
        }

        $skills = $filters['skills'] ?? [];
        if (is_array($skills) && $skills !== []) {
            $skillOr = [];
            $si = 0;
            foreach (array_slice($skills, 0, 8) as $skill) {
                $sk = 'sk' . $si;
                $skillOr[] = 'LOWER(s.`name`) LIKE :' . $sk;
                $params[$sk] = '%' . mb_strtolower(trim((string) $skill)) . '%';
                $si++;
            }
            $where[] = 'EXISTS (
                SELECT 1 FROM `resume_skills` rs
                INNER JOIN `skills` s ON s.`id` = rs.`skill_id`
                WHERE rs.`resume_id` = a.`resume_id`
                  AND rs.`deleted_at` IS NULL
                  AND (' . implode(' OR ', $skillOr) . ')
            )';
        }

        $education = $filters['education_keywords'] ?? [];
        if (is_array($education) && $education !== []) {
            $eduOr = [];
            $ei = 0;
            foreach (array_slice($education, 0, 6) as $edu) {
                $ek = 'ed' . $ei;
                $eduOr[] = '(LOWER(COALESCE(e.`degree`,\'\')) LIKE :' . $ek . 'a OR LOWER(COALESCE(e.`qualification_type`,\'\')) LIKE :' . $ek . 'b)';
                $like = '%' . mb_strtolower(trim((string) $edu)) . '%';
                $params[$ek . 'a'] = $like;
                $params[$ek . 'b'] = $like;
                $ei++;
            }
            $where[] = 'EXISTS (
                SELECT 1 FROM `education` e
                WHERE e.`resume_id` = a.`resume_id`
                  AND e.`deleted_at` IS NULL
                  AND (' . implode(' OR ', $eduOr) . ')
            )';
        }

        $certs = $filters['certifications'] ?? [];
        if (is_array($certs) && $certs !== []) {
            $certOr = [];
            $ci = 0;
            foreach (array_slice($certs, 0, 6) as $cert) {
                $ck = 'ct' . $ci;
                $certOr[] = 'LOWER(rc.`name`) LIKE :' . $ck;
                $params[$ck] = '%' . mb_strtolower(trim((string) $cert)) . '%';
                $ci++;
            }
            $where[] = 'EXISTS (
                SELECT 1 FROM `resume_certifications` rc
                WHERE rc.`resume_id` = a.`resume_id`
                  AND rc.`deleted_at` IS NULL
                  AND (' . implode(' OR ', $certOr) . ')
            )';
        }

        $sql = 'SELECT a.`id` AS application_id, a.`job_id`, a.`user_id`, a.`resume_id`, a.`status` AS application_status,
                       a.`applied_at`,
                       u.`full_name` AS applicant_name, u.`email` AS applicant_email,
                       j.`title` AS job_title, c.`name` AS job_country,
                       COALESCE(r.`overall_score`, 0) AS ranking_score,
                       COALESCE(r.`job_match_score`, m.`overall_score`, 0) AS match_score,
                       COALESCE(r.`resume_score`, 0) AS resume_score,
                       COALESCE(r.`skills_score`, m.`skills_score`, 0) AS skills_score,
                       COALESCE(r.`rank_position`, 9999) AS rank_position,
                       rp.`years_of_experience`
                FROM `applications` a
                INNER JOIN `users` u ON u.`id` = a.`user_id`
                INNER JOIN `jobs` j ON j.`id` = a.`job_id`
                LEFT JOIN `countries` c ON c.`id` = j.`country_id`
                LEFT JOIN `job_applicant_rankings` r
                       ON r.`application_id` = a.`id` AND r.`job_id` = a.`job_id` AND r.`deleted_at` IS NULL
                LEFT JOIN `resume_job_match_snapshots` m
                       ON m.`resume_id` = a.`resume_id` AND m.`job_id` = a.`job_id` AND m.`deleted_at` IS NULL
                LEFT JOIN `resume_professional` rp ON rp.`resume_id` = a.`resume_id`
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY COALESCE(r.`overall_score`, m.`overall_score`, 0) DESC,
                         COALESCE(r.`job_match_score`, m.`overall_score`, 0) DESC,
                         a.`applied_at` DESC
                LIMIT ' . $limit;

        return $this->fetchAll($sql, $params);
    }
}

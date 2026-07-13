<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\ResumeIntelligenceHistoryRepositoryInterface;

final class ResumeIntelligenceHistoryRepository extends BaseRepository implements ResumeIntelligenceHistoryRepositoryInterface
{
    protected string $table = 'resume_intelligence_history';

    public function append(int $resumeId, array $payload): int
    {
        $breakdown = json_encode($payload['score_breakdown'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $recs = json_encode($payload['recommendations'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $analysis = json_encode($payload['analysis_json'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($breakdown === false || $recs === false || $analysis === false) {
            throw new \RuntimeException('Unable to encode intelligence history JSON.');
        }

        $this->query(
            'INSERT INTO `resume_intelligence_history`
                (`resume_id`, `overall_score`, `ats_score`, `employer_readiness_score`, `keyword_match_score`,
                 `strength_level`, `score_breakdown`, `recommendations`, `analysis_json`, `target_role`,
                 `rules_version`, `calculated_at`)
             VALUES
                (:resume_id, :overall_score, :ats_score, :employer_readiness_score, :keyword_match_score,
                 :strength_level, :score_breakdown, :recommendations, :analysis_json, :target_role,
                 :rules_version, :calculated_at)',
            [
                'resume_id' => $resumeId,
                'overall_score' => max(0, min(100, (int) ($payload['overall_score'] ?? 0))),
                'ats_score' => max(0, min(100, (int) ($payload['ats_score'] ?? 0))),
                'employer_readiness_score' => max(0, min(100, (int) ($payload['employer_readiness_score'] ?? 0))),
                'keyword_match_score' => max(0, min(100, (int) ($payload['keyword_match_score'] ?? 0))),
                'strength_level' => (string) ($payload['strength_level'] ?? 'needs_improvement'),
                'score_breakdown' => $breakdown,
                'recommendations' => $recs,
                'analysis_json' => $analysis,
                'target_role' => $payload['target_role'] !== null && $payload['target_role'] !== ''
                    ? mb_substr((string) $payload['target_role'], 0, 200)
                    : null,
                'rules_version' => (string) ($payload['rules_version'] ?? ''),
                'calculated_at' => (string) ($payload['calculated_at'] ?? date('Y-m-d H:i:s')),
            ]
        );

        return (int) $this->pdo->lastInsertId();
    }

    public function listByResumeId(int $resumeId, int $limit = 25): array
    {
        if ($resumeId < 1) {
            return [];
        }

        $limit = max(1, min(100, $limit));

        return $this->fetchAll(
            'SELECT `id`, `resume_id`, `overall_score`, `ats_score`, `employer_readiness_score`, `keyword_match_score`,
                    `strength_level`, `target_role`, `rules_version`, `calculated_at`, `created_at`
             FROM `resume_intelligence_history`
             WHERE `resume_id` = :resume_id AND `deleted_at` IS NULL
             ORDER BY `calculated_at` DESC, `id` DESC
             LIMIT ' . $limit,
            ['resume_id' => $resumeId]
        );
    }

    public function findOwned(int $id, int $resumeId): ?array
    {
        if ($id < 1 || $resumeId < 1) {
            return null;
        }

        return $this->fetchOne(
            'SELECT * FROM `resume_intelligence_history`
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NULL
             LIMIT 1',
            ['id' => $id, 'resume_id' => $resumeId]
        );
    }

    public function softDelete(int $id, int $resumeId): bool
    {
        $stmt = $this->query(
            'UPDATE `resume_intelligence_history`
             SET `deleted_at` = CURRENT_TIMESTAMP(3)
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NULL',
            ['id' => $id, 'resume_id' => $resumeId]
        );

        return $stmt->rowCount() > 0;
    }

    public function softDeleteAllForResume(int $resumeId): int
    {
        if ($resumeId < 1) {
            return 0;
        }

        $stmt = $this->query(
            'UPDATE `resume_intelligence_history`
             SET `deleted_at` = CURRENT_TIMESTAMP(3)
             WHERE `resume_id` = :resume_id AND `deleted_at` IS NULL',
            ['resume_id' => $resumeId]
        );

        return $stmt->rowCount();
    }
}

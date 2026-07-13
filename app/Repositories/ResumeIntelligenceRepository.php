<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\ResumeIntelligenceRepositoryInterface;

final class ResumeIntelligenceRepository extends BaseRepository implements ResumeIntelligenceRepositoryInterface
{
    protected string $table = 'resume_intelligence_snapshots';

    public function findLatestByResumeId(int $resumeId): ?array
    {
        if ($resumeId < 1) {
            return null;
        }

        return $this->fetchOne(
            'SELECT * FROM `resume_intelligence_snapshots`
             WHERE `resume_id` = :resume_id
             LIMIT 1',
            ['resume_id' => $resumeId]
        );
    }

    public function upsert(int $resumeId, array $payload): void
    {
        $breakdown = json_encode($payload['score_breakdown'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $recs = json_encode($payload['recommendations'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $analysis = json_encode($payload['analysis_json'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($breakdown === false || $recs === false || $analysis === false) {
            throw new \RuntimeException('Unable to encode intelligence snapshot JSON.');
        }

        $this->query(
            'INSERT INTO `resume_intelligence_snapshots`
                (`resume_id`, `overall_score`, `ats_score`, `employer_readiness_score`, `keyword_match_score`,
                 `strength_level`, `score_breakdown`, `recommendations`, `analysis_json`, `rules_version`, `calculated_at`)
             VALUES
                (:resume_id, :overall_score, :ats_score, :employer_readiness_score, :keyword_match_score,
                 :strength_level, :score_breakdown, :recommendations, :analysis_json, :rules_version, :calculated_at)
             ON DUPLICATE KEY UPDATE
                `overall_score` = VALUES(`overall_score`),
                `ats_score` = VALUES(`ats_score`),
                `employer_readiness_score` = VALUES(`employer_readiness_score`),
                `keyword_match_score` = VALUES(`keyword_match_score`),
                `strength_level` = VALUES(`strength_level`),
                `score_breakdown` = VALUES(`score_breakdown`),
                `recommendations` = VALUES(`recommendations`),
                `analysis_json` = VALUES(`analysis_json`),
                `rules_version` = VALUES(`rules_version`),
                `calculated_at` = VALUES(`calculated_at`)',
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
                'rules_version' => (string) ($payload['rules_version'] ?? ''),
                'calculated_at' => (string) ($payload['calculated_at'] ?? date('Y-m-d H:i:s')),
            ]
        );
    }
}

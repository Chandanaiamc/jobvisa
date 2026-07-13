<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\CareerCoachSessionRepositoryInterface;

final class CareerCoachSessionRepository extends BaseRepository implements CareerCoachSessionRepositoryInterface
{
    protected string $table = 'career_coach_sessions';

    public function findByResumeId(int $resumeId): ?array
    {
        if ($resumeId < 1) {
            return null;
        }

        return $this->fetchOne(
            'SELECT * FROM `career_coach_sessions` WHERE `resume_id` = :resume_id LIMIT 1',
            ['resume_id' => $resumeId]
        );
    }

    public function upsert(int $resumeId, int $userId, array $payload): void
    {
        if ($resumeId < 1 || $userId < 1) {
            throw new \InvalidArgumentException('Invalid career coach session identifiers.');
        }

        $fields = [
            'summary_json' => $payload['summary_json'] ?? [],
            'skill_gaps_json' => $payload['skill_gaps_json'] ?? [],
            'next_roles_json' => $payload['next_roles_json'] ?? [],
            'learning_roadmap_json' => $payload['learning_roadmap_json'] ?? [],
            'certification_recs_json' => $payload['certification_recs_json'] ?? [],
            'portfolio_recs_json' => $payload['portfolio_recs_json'] ?? [],
            'job_opportunities_json' => $payload['job_opportunities_json'] ?? [],
            'context_scores_json' => $payload['context_scores_json'] ?? [],
        ];
        $encoded = [];
        foreach ($fields as $key => $value) {
            $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($json === false) {
                throw new \RuntimeException('Unable to encode career coach session JSON.');
            }
            $encoded[$key] = $json;
        }

        $this->query(
            'INSERT INTO `career_coach_sessions`
                (`resume_id`, `user_id`, `target_role`, `headline`, `summary_json`, `skill_gaps_json`,
                 `next_roles_json`, `learning_roadmap_json`, `certification_recs_json`, `portfolio_recs_json`,
                 `job_opportunities_json`, `context_scores_json`, `coach_version`, `calculated_at`)
             VALUES
                (:resume_id, :user_id, :target_role, :headline, :summary_json, :skill_gaps_json,
                 :next_roles_json, :learning_roadmap_json, :certification_recs_json, :portfolio_recs_json,
                 :job_opportunities_json, :context_scores_json, :coach_version, :calculated_at)
             ON DUPLICATE KEY UPDATE
                `user_id` = VALUES(`user_id`),
                `target_role` = VALUES(`target_role`),
                `headline` = VALUES(`headline`),
                `summary_json` = VALUES(`summary_json`),
                `skill_gaps_json` = VALUES(`skill_gaps_json`),
                `next_roles_json` = VALUES(`next_roles_json`),
                `learning_roadmap_json` = VALUES(`learning_roadmap_json`),
                `certification_recs_json` = VALUES(`certification_recs_json`),
                `portfolio_recs_json` = VALUES(`portfolio_recs_json`),
                `job_opportunities_json` = VALUES(`job_opportunities_json`),
                `context_scores_json` = VALUES(`context_scores_json`),
                `coach_version` = VALUES(`coach_version`),
                `calculated_at` = VALUES(`calculated_at`)',
            [
                'resume_id' => $resumeId,
                'user_id' => $userId,
                'target_role' => $payload['target_role'] ?? null,
                'headline' => mb_substr((string) ($payload['headline'] ?? ''), 0, 255),
                'summary_json' => $encoded['summary_json'],
                'skill_gaps_json' => $encoded['skill_gaps_json'],
                'next_roles_json' => $encoded['next_roles_json'],
                'learning_roadmap_json' => $encoded['learning_roadmap_json'],
                'certification_recs_json' => $encoded['certification_recs_json'],
                'portfolio_recs_json' => $encoded['portfolio_recs_json'],
                'job_opportunities_json' => $encoded['job_opportunities_json'],
                'context_scores_json' => $encoded['context_scores_json'],
                'coach_version' => (string) ($payload['coach_version'] ?? ''),
                'calculated_at' => (string) ($payload['calculated_at'] ?? date('Y-m-d H:i:s')),
            ]
        );
    }
}

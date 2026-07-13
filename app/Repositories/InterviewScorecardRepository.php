<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\InterviewScorecardRepositoryInterface;

final class InterviewScorecardRepository extends BaseRepository implements InterviewScorecardRepositoryInterface
{
    protected string $table = 'interview_scorecards';

    public function upsert(int $sessionId, array $payload): void
    {
        if ($sessionId < 1) {
            throw new \InvalidArgumentException('Invalid session id.');
        }

        $this->query(
            'INSERT INTO `interview_scorecards`
                (`session_id`, `technical_score`, `behavioral_score`, `communication_score`,
                 `culture_fit_score`, `overall_score`, `notes`, `hiring_recommendation`, `scored_by_user_id`)
             VALUES
                (:session_id, :technical_score, :behavioral_score, :communication_score,
                 :culture_fit_score, :overall_score, :notes, :hiring_recommendation, :scored_by_user_id)
             ON DUPLICATE KEY UPDATE
                `technical_score` = VALUES(`technical_score`),
                `behavioral_score` = VALUES(`behavioral_score`),
                `communication_score` = VALUES(`communication_score`),
                `culture_fit_score` = VALUES(`culture_fit_score`),
                `overall_score` = VALUES(`overall_score`),
                `notes` = VALUES(`notes`),
                `hiring_recommendation` = VALUES(`hiring_recommendation`),
                `scored_by_user_id` = VALUES(`scored_by_user_id`)',
            [
                'session_id' => $sessionId,
                'technical_score' => $this->clampScore($payload['technical_score'] ?? 0),
                'behavioral_score' => $this->clampScore($payload['behavioral_score'] ?? 0),
                'communication_score' => $this->clampScore($payload['communication_score'] ?? 0),
                'culture_fit_score' => $this->clampScore($payload['culture_fit_score'] ?? 0),
                'overall_score' => $this->clampScore($payload['overall_score'] ?? 0),
                'notes' => isset($payload['notes']) ? mb_substr((string) $payload['notes'], 0, 5000) : null,
                'hiring_recommendation' => mb_substr((string) ($payload['hiring_recommendation'] ?? 'pending'), 0, 32),
                'scored_by_user_id' => (int) ($payload['scored_by_user_id'] ?? 0),
            ]
        );
    }

    public function findBySessionId(int $sessionId): ?array
    {
        if ($sessionId < 1) {
            return null;
        }

        return $this->fetchOne(
            'SELECT * FROM `interview_scorecards` WHERE `session_id` = :sid LIMIT 1',
            ['sid' => $sessionId]
        );
    }

    private function clampScore(mixed $value): int
    {
        return max(0, min(100, (int) $value));
    }
}

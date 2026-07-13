<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\RecruiterSearchHistoryRepositoryInterface;

final class RecruiterSearchHistoryRepository extends BaseRepository implements RecruiterSearchHistoryRepositoryInterface
{
    protected string $table = 'recruiter_search_history';

    public function append(int $employerUserId, array $payload): int
    {
        $filters = json_encode($payload['parsed_filters'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $top = json_encode($payload['top_result_json'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $suggestions = json_encode($payload['suggestions_json'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($filters === false || $top === false || $suggestions === false) {
            throw new \RuntimeException('Unable to encode recruiter search history JSON.');
        }

        $this->query(
            'INSERT INTO `recruiter_search_history`
                (`employer_user_id`, `query_text`, `parsed_filters`, `result_count`, `top_result_json`, `suggestions_json`)
             VALUES
                (:employer_user_id, :query_text, :parsed_filters, :result_count, :top_result_json, :suggestions_json)',
            [
                'employer_user_id' => $employerUserId,
                'query_text' => mb_substr((string) ($payload['query_text'] ?? ''), 0, 500),
                'parsed_filters' => $filters,
                'result_count' => max(0, (int) ($payload['result_count'] ?? 0)),
                'top_result_json' => $top,
                'suggestions_json' => $suggestions,
            ]
        );

        return (int) $this->pdo->lastInsertId();
    }

    public function listByEmployer(int $employerUserId, int $limit = 20): array
    {
        if ($employerUserId < 1) {
            return [];
        }
        $limit = max(1, min(50, $limit));

        return $this->fetchAll(
            'SELECT * FROM `recruiter_search_history`
             WHERE `employer_user_id` = :uid AND `deleted_at` IS NULL
             ORDER BY `created_at` DESC, `id` DESC
             LIMIT ' . $limit,
            ['uid' => $employerUserId]
        );
    }

    public function softDelete(int $id, int $employerUserId): bool
    {
        $stmt = $this->query(
            'UPDATE `recruiter_search_history`
             SET `deleted_at` = CURRENT_TIMESTAMP(3)
             WHERE `id` = :id AND `employer_user_id` = :uid AND `deleted_at` IS NULL',
            ['id' => $id, 'uid' => $employerUserId]
        );

        return $stmt->rowCount() > 0;
    }

    public function softDeleteAllForEmployer(int $employerUserId): int
    {
        if ($employerUserId < 1) {
            return 0;
        }
        $stmt = $this->query(
            'UPDATE `recruiter_search_history`
             SET `deleted_at` = CURRENT_TIMESTAMP(3)
             WHERE `employer_user_id` = :uid AND `deleted_at` IS NULL',
            ['uid' => $employerUserId]
        );

        return $stmt->rowCount();
    }
}

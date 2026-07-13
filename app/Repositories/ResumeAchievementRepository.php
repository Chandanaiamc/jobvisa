<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\ResumeAchievementRepositoryInterface;

final class ResumeAchievementRepository extends BaseRepository implements ResumeAchievementRepositoryInterface
{
    protected string $table = 'resume_achievements';

    private const SELECT_JOINS = 'SELECT a.*,
                p.`title` AS `project_title`,
                c.`name` AS `country_name`,
                ci.`name` AS `city_name`
             FROM `resume_achievements` a
             LEFT JOIN `resume_projects` p ON p.`id` = a.`project_id` AND p.`deleted_at` IS NULL
             LEFT JOIN `countries` c ON c.`id` = a.`country_id`
             LEFT JOIN `cities` ci ON ci.`id` = a.`city_id`';

    public function listByResumeId(int $resumeId, ?string $query = null): array
    {
        if ($resumeId < 1) {
            return [];
        }

        $sql = self::SELECT_JOINS . '
                WHERE a.`resume_id` = :resume_id AND a.`deleted_at` IS NULL';
        $params = ['resume_id' => $resumeId];

        $query = $query !== null ? trim($query) : '';
        if ($query !== '') {
            $sql .= ' AND (
                a.`title` LIKE :q1 OR a.`issuer` LIKE :q2 OR a.`description` LIKE :q3
                OR a.`achievement_type` LIKE :q4 OR p.`title` LIKE :q5
                OR a.`rank_or_placement` LIKE :q6 OR a.`remarks` LIKE :q7
                OR c.`name` LIKE :q8 OR ci.`name` LIKE :q9
            )';
            $like = '%' . $query . '%';
            foreach (range(1, 9) as $i) {
                $params['q' . $i] = $like;
            }
        }

        $sql .= ' ORDER BY a.`is_featured` DESC, a.`sort_order` ASC, a.`achievement_date` DESC, a.`id` DESC';

        return $this->fetchAll($sql, $params);
    }

    public function listPublicByResumeId(int $resumeId): array
    {
        if ($resumeId < 1) {
            return [];
        }

        return $this->fetchAll(
            self::SELECT_JOINS . '
             WHERE a.`resume_id` = :resume_id
               AND a.`deleted_at` IS NULL
               AND a.`visibility` = \'public\'
               AND a.`status` = \'active\'
             ORDER BY a.`is_featured` DESC, a.`sort_order` ASC, a.`achievement_date` DESC, a.`id` DESC',
            ['resume_id' => $resumeId]
        );
    }

    public function listDeletedByResumeId(int $resumeId): array
    {
        if ($resumeId < 1) {
            return [];
        }

        return $this->fetchAll(
            'SELECT a.*, p.`title` AS `project_title`, c.`name` AS `country_name`, ci.`name` AS `city_name`
             FROM `resume_achievements` a
             LEFT JOIN `resume_projects` p ON p.`id` = a.`project_id`
             LEFT JOIN `countries` c ON c.`id` = a.`country_id`
             LEFT JOIN `cities` ci ON ci.`id` = a.`city_id`
             WHERE a.`resume_id` = :resume_id AND a.`deleted_at` IS NOT NULL
             ORDER BY a.`deleted_at` DESC, a.`id` DESC',
            ['resume_id' => $resumeId]
        );
    }

    public function search(int $resumeId, string $query, int $limit = 20): array
    {
        if ($resumeId < 1) {
            return [];
        }

        $query = trim($query);
        $limit = max(1, min(50, $limit));

        if ($query === '') {
            return array_slice($this->listByResumeId($resumeId), 0, $limit);
        }

        $like = '%' . $query . '%';

        return $this->fetchAll(
            'SELECT a.`id`, a.`title`, a.`issuer`, a.`achievement_type`, a.`award_level`,
                    a.`is_featured`, a.`visibility`, p.`title` AS `project_title`,
                    c.`name` AS `country_name`, ci.`name` AS `city_name`
             FROM `resume_achievements` a
             LEFT JOIN `resume_projects` p ON p.`id` = a.`project_id` AND p.`deleted_at` IS NULL
             LEFT JOIN `countries` c ON c.`id` = a.`country_id`
             LEFT JOIN `cities` ci ON ci.`id` = a.`city_id`
             WHERE a.`resume_id` = :resume_id AND a.`deleted_at` IS NULL
               AND (a.`title` LIKE :q1 OR a.`issuer` LIKE :q2 OR p.`title` LIKE :q3
                    OR c.`name` LIKE :q4 OR ci.`name` LIKE :q5 OR a.`rank_or_placement` LIKE :q6)
             ORDER BY a.`is_featured` DESC, a.`sort_order` ASC, a.`id` DESC
             LIMIT ' . $limit,
            [
                'resume_id' => $resumeId,
                'q1' => $like,
                'q2' => $like,
                'q3' => $like,
                'q4' => $like,
                'q5' => $like,
                'q6' => $like,
            ]
        );
    }

    public function findOwned(int $id, int $resumeId): ?array
    {
        return $this->fetchOne(
            self::SELECT_JOINS . '
             WHERE a.`id` = :id AND a.`resume_id` = :resume_id AND a.`deleted_at` IS NULL
             LIMIT 1',
            ['id' => $id, 'resume_id' => $resumeId]
        );
    }

    public function findDeletedOwned(int $id, int $resumeId): ?array
    {
        return $this->fetchOne(
            'SELECT a.*, p.`title` AS `project_title`, c.`name` AS `country_name`, ci.`name` AS `city_name`
             FROM `resume_achievements` a
             LEFT JOIN `resume_projects` p ON p.`id` = a.`project_id`
             LEFT JOIN `countries` c ON c.`id` = a.`country_id`
             LEFT JOIN `cities` ci ON ci.`id` = a.`city_id`
             WHERE a.`id` = :id AND a.`resume_id` = :resume_id AND a.`deleted_at` IS NOT NULL
             LIMIT 1',
            ['id' => $id, 'resume_id' => $resumeId]
        );
    }

    public function create(int $resumeId, array $data): int
    {
        $this->query(
            'INSERT INTO `resume_achievements`
                (`resume_id`, `project_id`, `country_id`, `city_id`, `title`, `issuer`, `description`, `remarks`,
                 `achievement_type`, `award_level`, `rank_or_placement`, `achievement_date`, `credential_url`,
                 `certificate_path`, `certificate_original_name`, `certificate_mime`, `certificate_size`,
                 `is_featured`, `visibility`, `sort_order`, `status`)
             VALUES
                (:resume_id, :project_id, :country_id, :city_id, :title, :issuer, :description, :remarks,
                 :achievement_type, :award_level, :rank_or_placement, :achievement_date, :credential_url,
                 :certificate_path, :certificate_original_name, :certificate_mime, :certificate_size,
                 :is_featured, :visibility, :sort_order, :status)',
            $this->bindPayload($resumeId, $data)
        );

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, int $resumeId, array $data): bool
    {
        if ($this->findOwned($id, $resumeId) === null) {
            return false;
        }

        $payload = $this->bindPayload($resumeId, $data);
        unset(
            $payload['certificate_path'],
            $payload['certificate_original_name'],
            $payload['certificate_mime'],
            $payload['certificate_size']
        );
        $payload['id'] = $id;

        $this->query(
            'UPDATE `resume_achievements` SET
                `project_id` = :project_id,
                `country_id` = :country_id,
                `city_id` = :city_id,
                `title` = :title,
                `issuer` = :issuer,
                `description` = :description,
                `remarks` = :remarks,
                `achievement_type` = :achievement_type,
                `award_level` = :award_level,
                `rank_or_placement` = :rank_or_placement,
                `achievement_date` = :achievement_date,
                `credential_url` = :credential_url,
                `is_featured` = :is_featured,
                `visibility` = :visibility,
                `sort_order` = :sort_order,
                `status` = :status
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NULL',
            $payload
        );

        return true;
    }

    public function delete(int $id, int $resumeId): bool
    {
        if ($this->findOwned($id, $resumeId) === null) {
            return false;
        }

        // Soft delete only — certificate files are retained for restore / later cleanup.
        $this->query(
            'UPDATE `resume_achievements`
             SET `deleted_at` = CURRENT_TIMESTAMP(3), `status` = \'archived\', `is_featured` = 0
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NULL',
            ['id' => $id, 'resume_id' => $resumeId]
        );

        return true;
    }

    public function restore(int $id, int $resumeId): bool
    {
        if ($this->findDeletedOwned($id, $resumeId) === null) {
            return false;
        }

        $this->query(
            'UPDATE `resume_achievements`
             SET `deleted_at` = NULL, `status` = \'active\'
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NOT NULL',
            ['id' => $id, 'resume_id' => $resumeId]
        );

        return true;
    }

    public function updateCertificateMeta(int $id, int $resumeId, array $meta): bool
    {
        if ($this->findOwned($id, $resumeId) === null) {
            return false;
        }

        $this->query(
            'UPDATE `resume_achievements` SET
                `certificate_path` = :path,
                `certificate_original_name` = :original_name,
                `certificate_mime` = :mime,
                `certificate_size` = :size
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NULL',
            [
                'path' => $meta['path'] ?? null,
                'original_name' => $meta['original_name'] ?? null,
                'mime' => $meta['mime'] ?? null,
                'size' => $meta['size'] ?? null,
                'id' => $id,
                'resume_id' => $resumeId,
            ]
        );

        return true;
    }

    public function updateCertificatePath(int $id, int $resumeId, ?string $path): bool
    {
        return $this->updateCertificateMeta($id, $resumeId, [
            'path' => $path,
            'original_name' => $path === null ? null : null,
            'mime' => null,
            'size' => null,
        ]);
    }

    public function reorder(int $resumeId, array $orderedIds): void
    {
        $order = 0;
        foreach ($orderedIds as $id) {
            $id = (int) $id;
            if ($id < 1) {
                continue;
            }
            $this->query(
                'UPDATE `resume_achievements` SET `sort_order` = :sort_order
                 WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NULL',
                ['sort_order' => $order++, 'id' => $id, 'resume_id' => $resumeId]
            );
        }
    }

    public function countActive(int $resumeId): int
    {
        if ($resumeId < 1) {
            return 0;
        }

        return (int) $this->query(
            'SELECT COUNT(*) FROM `resume_achievements`
             WHERE `resume_id` = :resume_id AND `deleted_at` IS NULL',
            ['resume_id' => $resumeId]
        )->fetchColumn();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function bindPayload(int $resumeId, array $data): array
    {
        return [
            'resume_id' => $resumeId,
            'project_id' => $this->nullId($data['project_id'] ?? null),
            'country_id' => $this->nullId($data['country_id'] ?? null),
            'city_id' => $this->nullId($data['city_id'] ?? null),
            'title' => $data['title'],
            'issuer' => $data['issuer'] ?? null,
            'description' => $data['description'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'achievement_type' => $data['achievement_type'] ?? null,
            'award_level' => $data['award_level'] ?? null,
            'rank_or_placement' => $data['rank_or_placement'] ?? null,
            'achievement_date' => $data['achievement_date'] ?? null,
            'credential_url' => $data['credential_url'] ?? null,
            'certificate_path' => $data['certificate_path'] ?? null,
            'certificate_original_name' => $data['certificate_original_name'] ?? null,
            'certificate_mime' => $data['certificate_mime'] ?? null,
            'certificate_size' => isset($data['certificate_size']) && $data['certificate_size'] !== null
                ? (int) $data['certificate_size']
                : null,
            'is_featured' => !empty($data['is_featured']) ? 1 : 0,
            'visibility' => (string) ($data['visibility'] ?? 'public'),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'status' => (string) ($data['status'] ?? 'active'),
        ];
    }

    private function nullId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $id = (int) $value;

        return $id > 0 ? $id : null;
    }
}

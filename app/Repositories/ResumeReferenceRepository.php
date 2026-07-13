<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\ResumeReferenceRepositoryInterface;

final class ResumeReferenceRepository extends BaseRepository implements ResumeReferenceRepositoryInterface
{
    protected string $table = 'resume_references';

    private const SELECT_JOINS = 'SELECT r.*,
                pr.`title` AS `project_title`,
                c.`name` AS `country_name`,
                ci.`name` AS `city_name`
             FROM `resume_references` r
             LEFT JOIN `resume_projects` pr ON pr.`id` = r.`project_id` AND pr.`deleted_at` IS NULL
             LEFT JOIN `countries` c ON c.`id` = r.`country_id`
             LEFT JOIN `cities` ci ON ci.`id` = r.`city_id`';

    /** Public list never selects email/phone. */
    private const SELECT_PUBLIC = 'SELECT r.`id`, r.`resume_id`, r.`project_id`, r.`country_id`, r.`city_id`,
                r.`name`, r.`designation`, r.`company`, r.`relationship`, r.`years_known`,
                r.`permission_to_contact`, r.`notes`, r.`is_featured`, r.`visibility`, r.`status`,
                r.`sort_order`, r.`created_at`, r.`updated_at`, r.`deleted_at`,
                pr.`title` AS `project_title`,
                c.`name` AS `country_name`,
                ci.`name` AS `city_name`
             FROM `resume_references` r
             LEFT JOIN `resume_projects` pr ON pr.`id` = r.`project_id` AND pr.`deleted_at` IS NULL
             LEFT JOIN `countries` c ON c.`id` = r.`country_id`
             LEFT JOIN `cities` ci ON ci.`id` = r.`city_id`';

    public function listByResumeId(int $resumeId, array $filters = [], int $page = 1, int $perPage = 10): array
    {
        if ($resumeId < 1) {
            return ['items' => [], 'total' => 0];
        }

        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $offset = ($page - 1) * $perPage;

        $where = ['r.`resume_id` = :resume_id', 'r.`deleted_at` IS NULL'];
        $params = ['resume_id' => $resumeId];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(
                r.`name` LIKE :q1 OR r.`company` LIKE :q2 OR r.`designation` LIKE :q3
                OR r.`email` LIKE :q4 OR r.`relationship` LIKE :q5
            )';
            $like = '%' . $q . '%';
            foreach (range(1, 5) as $i) {
                $params['q' . $i] = $like;
            }
        }

        if (isset($filters['is_featured']) && $filters['is_featured'] !== '') {
            $where[] = 'r.`is_featured` = :is_featured';
            $params['is_featured'] = !empty($filters['is_featured']) ? 1 : 0;
        }
        if (!empty($filters['visibility'])) {
            $where[] = 'r.`visibility` = :visibility';
            $params['visibility'] = (string) $filters['visibility'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'r.`status` = :status';
            $params['status'] = (string) $filters['status'];
        }
        if (!empty($filters['country_id'])) {
            $where[] = 'r.`country_id` = :country_id';
            $params['country_id'] = (int) $filters['country_id'];
        }
        if (isset($filters['permission_to_contact']) && $filters['permission_to_contact'] !== '') {
            $where[] = 'r.`permission_to_contact` = :permission_to_contact';
            $params['permission_to_contact'] = !empty($filters['permission_to_contact']) ? 1 : 0;
        }
        if (!empty($filters['relationship'])) {
            $where[] = 'r.`relationship` = :relationship';
            $params['relationship'] = (string) $filters['relationship'];
        }

        $whereSql = implode(' AND ', $where);
        $order = $this->orderSql((string) ($filters['sort'] ?? 'sort_order'));

        $total = (int) $this->query(
            'SELECT COUNT(*) FROM `resume_references` r WHERE ' . $whereSql,
            $params
        )->fetchColumn();

        $items = $this->fetchAll(
            self::SELECT_JOINS . ' WHERE ' . $whereSql . ' ' . $order . ' LIMIT ' . $perPage . ' OFFSET ' . $offset,
            $params
        );

        return ['items' => $items, 'total' => $total];
    }

    public function listPublicByResumeId(int $resumeId): array
    {
        if ($resumeId < 1) {
            return [];
        }

        return $this->fetchAll(
            self::SELECT_PUBLIC . '
             WHERE r.`resume_id` = :resume_id
               AND r.`deleted_at` IS NULL
               AND r.`visibility` = \'public\'
               AND r.`status` = \'active\'
             ORDER BY r.`is_featured` DESC, r.`sort_order` ASC, r.`id` DESC',
            ['resume_id' => $resumeId]
        );
    }

    public function listForEmployerByResumeId(int $resumeId): array
    {
        if ($resumeId < 1) {
            return [];
        }

        return $this->fetchAll(
            self::SELECT_JOINS . '
             WHERE r.`resume_id` = :resume_id
               AND r.`deleted_at` IS NULL
               AND r.`visibility` IN (\'public\', \'employers\')
               AND r.`status` = \'active\'
             ORDER BY r.`is_featured` DESC, r.`sort_order` ASC, r.`id` DESC',
            ['resume_id' => $resumeId]
        );
    }

    public function listDeletedByResumeId(int $resumeId): array
    {
        if ($resumeId < 1) {
            return [];
        }

        return $this->fetchAll(
            'SELECT r.*, pr.`title` AS `project_title`, c.`name` AS `country_name`, ci.`name` AS `city_name`
             FROM `resume_references` r
             LEFT JOIN `resume_projects` pr ON pr.`id` = r.`project_id`
             LEFT JOIN `countries` c ON c.`id` = r.`country_id`
             LEFT JOIN `cities` ci ON ci.`id` = r.`city_id`
             WHERE r.`resume_id` = :resume_id AND r.`deleted_at` IS NOT NULL
             ORDER BY r.`deleted_at` DESC, r.`id` DESC',
            ['resume_id' => $resumeId]
        );
    }

    public function search(int $resumeId, string $query, int $limit = 20): array
    {
        $result = $this->listByResumeId($resumeId, ['q' => $query, 'sort' => 'featured'], 1, max(1, min(50, $limit)));

        return $result['items'];
    }

    public function findOwned(int $id, int $resumeId): ?array
    {
        return $this->fetchOne(
            self::SELECT_JOINS . '
             WHERE r.`id` = :id AND r.`resume_id` = :resume_id AND r.`deleted_at` IS NULL
             LIMIT 1',
            ['id' => $id, 'resume_id' => $resumeId]
        );
    }

    public function findDeletedOwned(int $id, int $resumeId): ?array
    {
        return $this->fetchOne(
            'SELECT r.*, pr.`title` AS `project_title`, c.`name` AS `country_name`, ci.`name` AS `city_name`
             FROM `resume_references` r
             LEFT JOIN `resume_projects` pr ON pr.`id` = r.`project_id`
             LEFT JOIN `countries` c ON c.`id` = r.`country_id`
             LEFT JOIN `cities` ci ON ci.`id` = r.`city_id`
             WHERE r.`id` = :id AND r.`resume_id` = :resume_id AND r.`deleted_at` IS NOT NULL
             LIMIT 1',
            ['id' => $id, 'resume_id' => $resumeId]
        );
    }

    public function findDuplicate(int $resumeId, string $name, ?string $company, ?int $exceptId = null): ?array
    {
        $sql = 'SELECT `id`, `name`, `company`
                FROM `resume_references`
                WHERE `resume_id` = :resume_id AND `deleted_at` IS NULL
                  AND LOWER(TRIM(`name`)) = LOWER(TRIM(:name))';
        $params = ['resume_id' => $resumeId, 'name' => $name];

        if ($company === null || $company === '') {
            $sql .= ' AND (`company` IS NULL OR `company` = \'\')';
        } else {
            $sql .= ' AND LOWER(TRIM(`company`)) = LOWER(TRIM(:company))';
            $params['company'] = $company;
        }

        if ($exceptId !== null && $exceptId > 0) {
            $sql .= ' AND `id` <> :except_id';
            $params['except_id'] = $exceptId;
        }

        $sql .= ' LIMIT 1';

        return $this->fetchOne($sql, $params);
    }

    public function create(int $resumeId, array $data): int
    {
        $this->query(
            'INSERT INTO `resume_references`
                (`resume_id`, `project_id`, `country_id`, `city_id`, `name`, `designation`, `company`,
                 `email`, `phone`, `relationship`, `years_known`, `permission_to_contact`, `notes`,
                 `is_featured`, `visibility`, `status`, `sort_order`)
             VALUES
                (:resume_id, :project_id, :country_id, :city_id, :name, :designation, :company,
                 :email, :phone, :relationship, :years_known, :permission_to_contact, :notes,
                 :is_featured, :visibility, :status, :sort_order)',
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
        $payload['id'] = $id;

        $this->query(
            'UPDATE `resume_references` SET
                `project_id` = :project_id,
                `country_id` = :country_id,
                `city_id` = :city_id,
                `name` = :name,
                `designation` = :designation,
                `company` = :company,
                `email` = :email,
                `phone` = :phone,
                `relationship` = :relationship,
                `years_known` = :years_known,
                `permission_to_contact` = :permission_to_contact,
                `notes` = :notes,
                `is_featured` = :is_featured,
                `visibility` = :visibility,
                `status` = :status,
                `sort_order` = :sort_order
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

        $this->query(
            'UPDATE `resume_references`
             SET `deleted_at` = CURRENT_TIMESTAMP(3), `status` = \'hidden\', `is_featured` = 0
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
            'UPDATE `resume_references`
             SET `deleted_at` = NULL, `status` = \'active\'
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NOT NULL',
            ['id' => $id, 'resume_id' => $resumeId]
        );

        return true;
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
                'UPDATE `resume_references` SET `sort_order` = :sort_order
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
            'SELECT COUNT(*) FROM `resume_references`
             WHERE `resume_id` = :resume_id AND `deleted_at` IS NULL',
            ['resume_id' => $resumeId]
        )->fetchColumn();
    }

    private function orderSql(string $sort): string
    {
        return match ($sort) {
            'newest' => 'ORDER BY r.`created_at` DESC, r.`id` DESC',
            'oldest' => 'ORDER BY r.`created_at` ASC, r.`id` ASC',
            'name' => 'ORDER BY r.`name` ASC, r.`id` DESC',
            'featured' => 'ORDER BY r.`is_featured` DESC, r.`sort_order` ASC, r.`id` DESC',
            default => 'ORDER BY r.`is_featured` DESC, r.`sort_order` ASC, r.`id` DESC',
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function bindPayload(int $resumeId, array $data): array
    {
        $yearsKnown = null;
        if (isset($data['years_known']) && $data['years_known'] !== null && $data['years_known'] !== '') {
            $yearsKnown = round((float) $data['years_known'], 1);
        }

        return [
            'resume_id' => $resumeId,
            'project_id' => $this->nullId($data['project_id'] ?? null),
            'country_id' => $this->nullId($data['country_id'] ?? null),
            'city_id' => $this->nullId($data['city_id'] ?? null),
            'name' => $data['name'],
            'designation' => $data['designation'] ?? null,
            'company' => $data['company'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'relationship' => $data['relationship'] ?? null,
            'years_known' => $yearsKnown,
            'permission_to_contact' => !empty($data['permission_to_contact']) ? 1 : 0,
            'notes' => $data['notes'] ?? null,
            'is_featured' => !empty($data['is_featured']) ? 1 : 0,
            'visibility' => (string) ($data['visibility'] ?? 'private'),
            'status' => (string) ($data['status'] ?? 'active'),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
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

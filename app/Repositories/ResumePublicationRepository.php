<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\ResumePublicationRepositoryInterface;

final class ResumePublicationRepository extends BaseRepository implements ResumePublicationRepositoryInterface
{
    protected string $table = 'resume_publications';

    private const SELECT_JOINS = 'SELECT p.*,
                pr.`title` AS `project_title`,
                c.`name` AS `country_name`,
                ci.`name` AS `city_name`
             FROM `resume_publications` p
             LEFT JOIN `resume_projects` pr ON pr.`id` = p.`project_id` AND pr.`deleted_at` IS NULL
             LEFT JOIN `countries` c ON c.`id` = p.`country_id`
             LEFT JOIN `cities` ci ON ci.`id` = p.`city_id`';

    public function listByResumeId(int $resumeId, array $filters = [], int $page = 1, int $perPage = 10): array
    {
        if ($resumeId < 1) {
            return ['items' => [], 'total' => 0];
        }

        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $offset = ($page - 1) * $perPage;

        $where = ['p.`resume_id` = :resume_id', 'p.`deleted_at` IS NULL'];
        $params = ['resume_id' => $resumeId];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(
                p.`title` LIKE :q1 OR p.`publisher` LIKE :q2 OR p.`authors` LIKE :q3
                OR p.`doi` LIKE :q4 OR p.`isbn` LIKE :q5 OR p.`issn` LIKE :q6
                OR p.`patent_number` LIKE :q7 OR p.`conference_name` LIKE :q8 OR p.`keywords` LIKE :q9
            )';
            $like = '%' . $q . '%';
            foreach (range(1, 9) as $i) {
                $params['q' . $i] = $like;
            }
        }

        if (!empty($filters['publication_type'])) {
            $where[] = 'p.`publication_type` = :publication_type';
            $params['publication_type'] = (string) $filters['publication_type'];
        }
        if (isset($filters['publication_year']) && $filters['publication_year'] !== '' && $filters['publication_year'] !== null) {
            $where[] = 'p.`publication_year` = :publication_year';
            $params['publication_year'] = (int) $filters['publication_year'];
        }
        if (isset($filters['is_peer_reviewed']) && $filters['is_peer_reviewed'] !== '') {
            $where[] = 'p.`is_peer_reviewed` = :is_peer_reviewed';
            $params['is_peer_reviewed'] = !empty($filters['is_peer_reviewed']) ? 1 : 0;
        }
        if (isset($filters['is_featured']) && $filters['is_featured'] !== '') {
            $where[] = 'p.`is_featured` = :is_featured';
            $params['is_featured'] = !empty($filters['is_featured']) ? 1 : 0;
        }
        if (!empty($filters['visibility'])) {
            $where[] = 'p.`visibility` = :visibility';
            $params['visibility'] = (string) $filters['visibility'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'p.`status` = :status';
            $params['status'] = (string) $filters['status'];
        }
        if (!empty($filters['country_id'])) {
            $where[] = 'p.`country_id` = :country_id';
            $params['country_id'] = (int) $filters['country_id'];
        }

        $whereSql = implode(' AND ', $where);
        $order = $this->orderSql((string) ($filters['sort'] ?? 'sort_order'));

        $total = (int) $this->query(
            'SELECT COUNT(*) FROM `resume_publications` p WHERE ' . $whereSql,
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
            self::SELECT_JOINS . '
             WHERE p.`resume_id` = :resume_id
               AND p.`deleted_at` IS NULL
               AND p.`visibility` = \'public\'
               AND p.`status` = \'active\'
             ORDER BY p.`is_featured` DESC, p.`sort_order` ASC, p.`publication_year` DESC, p.`id` DESC',
            ['resume_id' => $resumeId]
        );
    }

    public function listDeletedByResumeId(int $resumeId): array
    {
        if ($resumeId < 1) {
            return [];
        }

        return $this->fetchAll(
            'SELECT p.*, pr.`title` AS `project_title`, c.`name` AS `country_name`, ci.`name` AS `city_name`
             FROM `resume_publications` p
             LEFT JOIN `resume_projects` pr ON pr.`id` = p.`project_id`
             LEFT JOIN `countries` c ON c.`id` = p.`country_id`
             LEFT JOIN `cities` ci ON ci.`id` = p.`city_id`
             WHERE p.`resume_id` = :resume_id AND p.`deleted_at` IS NOT NULL
             ORDER BY p.`deleted_at` DESC, p.`id` DESC',
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
             WHERE p.`id` = :id AND p.`resume_id` = :resume_id AND p.`deleted_at` IS NULL
             LIMIT 1',
            ['id' => $id, 'resume_id' => $resumeId]
        );
    }

    public function findDeletedOwned(int $id, int $resumeId): ?array
    {
        return $this->fetchOne(
            'SELECT p.*, pr.`title` AS `project_title`, c.`name` AS `country_name`, ci.`name` AS `city_name`
             FROM `resume_publications` p
             LEFT JOIN `resume_projects` pr ON pr.`id` = p.`project_id`
             LEFT JOIN `countries` c ON c.`id` = p.`country_id`
             LEFT JOIN `cities` ci ON ci.`id` = p.`city_id`
             WHERE p.`id` = :id AND p.`resume_id` = :resume_id AND p.`deleted_at` IS NOT NULL
             LIMIT 1',
            ['id' => $id, 'resume_id' => $resumeId]
        );
    }

    public function findDuplicate(int $resumeId, string $title, ?string $publisher, ?int $year, ?int $exceptId = null): ?array
    {
        $sql = 'SELECT `id`, `title`, `publisher`, `publication_year`
                FROM `resume_publications`
                WHERE `resume_id` = :resume_id AND `deleted_at` IS NULL
                  AND LOWER(TRIM(`title`)) = LOWER(TRIM(:title))';
        $params = ['resume_id' => $resumeId, 'title' => $title];

        if ($publisher === null || $publisher === '') {
            $sql .= ' AND (`publisher` IS NULL OR `publisher` = \'\')';
        } else {
            $sql .= ' AND LOWER(TRIM(`publisher`)) = LOWER(TRIM(:publisher))';
            $params['publisher'] = $publisher;
        }

        if ($year === null) {
            $sql .= ' AND `publication_year` IS NULL';
        } else {
            $sql .= ' AND `publication_year` = :publication_year';
            $params['publication_year'] = $year;
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
            'INSERT INTO `resume_publications`
                (`resume_id`, `project_id`, `country_id`, `city_id`, `title`, `publication_type`, `publisher`,
                 `authors`, `user_contribution`, `publication_date`, `publication_year`, `volume`, `issue`,
                 `page_range`, `doi`, `isbn`, `issn`, `patent_number`, `conference_name`, `abstract_summary`,
                 `keywords`, `publication_url`, `document_path`, `document_original_name`, `document_mime`,
                 `document_size`, `is_peer_reviewed`, `is_featured`, `visibility`, `status`, `sort_order`)
             VALUES
                (:resume_id, :project_id, :country_id, :city_id, :title, :publication_type, :publisher,
                 :authors, :user_contribution, :publication_date, :publication_year, :volume, :issue,
                 :page_range, :doi, :isbn, :issn, :patent_number, :conference_name, :abstract_summary,
                 :keywords, :publication_url, :document_path, :document_original_name, :document_mime,
                 :document_size, :is_peer_reviewed, :is_featured, :visibility, :status, :sort_order)',
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
            $payload['document_path'],
            $payload['document_original_name'],
            $payload['document_mime'],
            $payload['document_size']
        );
        $payload['id'] = $id;

        $this->query(
            'UPDATE `resume_publications` SET
                `project_id` = :project_id,
                `country_id` = :country_id,
                `city_id` = :city_id,
                `title` = :title,
                `publication_type` = :publication_type,
                `publisher` = :publisher,
                `authors` = :authors,
                `user_contribution` = :user_contribution,
                `publication_date` = :publication_date,
                `publication_year` = :publication_year,
                `volume` = :volume,
                `issue` = :issue,
                `page_range` = :page_range,
                `doi` = :doi,
                `isbn` = :isbn,
                `issn` = :issn,
                `patent_number` = :patent_number,
                `conference_name` = :conference_name,
                `abstract_summary` = :abstract_summary,
                `keywords` = :keywords,
                `publication_url` = :publication_url,
                `is_peer_reviewed` = :is_peer_reviewed,
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
            'UPDATE `resume_publications`
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
            'UPDATE `resume_publications`
             SET `deleted_at` = NULL, `status` = \'active\'
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NOT NULL',
            ['id' => $id, 'resume_id' => $resumeId]
        );

        return true;
    }

    public function updateDocumentMeta(int $id, int $resumeId, array $meta): bool
    {
        if ($this->findOwned($id, $resumeId) === null) {
            return false;
        }

        $this->query(
            'UPDATE `resume_publications` SET
                `document_path` = :path,
                `document_original_name` = :original_name,
                `document_mime` = :mime,
                `document_size` = :size
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

    public function reorder(int $resumeId, array $orderedIds): void
    {
        $order = 0;
        foreach ($orderedIds as $id) {
            $id = (int) $id;
            if ($id < 1) {
                continue;
            }
            $this->query(
                'UPDATE `resume_publications` SET `sort_order` = :sort_order
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
            'SELECT COUNT(*) FROM `resume_publications`
             WHERE `resume_id` = :resume_id AND `deleted_at` IS NULL',
            ['resume_id' => $resumeId]
        )->fetchColumn();
    }

    private function orderSql(string $sort): string
    {
        return match ($sort) {
            'newest' => 'ORDER BY p.`created_at` DESC, p.`id` DESC',
            'oldest' => 'ORDER BY p.`created_at` ASC, p.`id` ASC',
            'title' => 'ORDER BY p.`title` ASC, p.`id` DESC',
            'year' => 'ORDER BY p.`publication_year` DESC, p.`id` DESC',
            'featured' => 'ORDER BY p.`is_featured` DESC, p.`sort_order` ASC, p.`id` DESC',
            default => 'ORDER BY p.`is_featured` DESC, p.`sort_order` ASC, p.`publication_year` DESC, p.`id` DESC',
        };
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
            'publication_type' => $data['publication_type'],
            'publisher' => $data['publisher'] ?? null,
            'authors' => $data['authors'] ?? null,
            'user_contribution' => $data['user_contribution'] ?? null,
            'publication_date' => $data['publication_date'] ?? null,
            'publication_year' => isset($data['publication_year']) && $data['publication_year'] !== null && $data['publication_year'] !== ''
                ? (int) $data['publication_year']
                : null,
            'volume' => $data['volume'] ?? null,
            'issue' => $data['issue'] ?? null,
            'page_range' => $data['page_range'] ?? null,
            'doi' => $data['doi'] ?? null,
            'isbn' => $data['isbn'] ?? null,
            'issn' => $data['issn'] ?? null,
            'patent_number' => $data['patent_number'] ?? null,
            'conference_name' => $data['conference_name'] ?? null,
            'abstract_summary' => $data['abstract_summary'] ?? null,
            'keywords' => $data['keywords'] ?? null,
            'publication_url' => $data['publication_url'] ?? null,
            'document_path' => $data['document_path'] ?? null,
            'document_original_name' => $data['document_original_name'] ?? null,
            'document_mime' => $data['document_mime'] ?? null,
            'document_size' => isset($data['document_size']) && $data['document_size'] !== null
                ? (int) $data['document_size']
                : null,
            'is_peer_reviewed' => !empty($data['is_peer_reviewed']) ? 1 : 0,
            'is_featured' => !empty($data['is_featured']) ? 1 : 0,
            'visibility' => (string) ($data['visibility'] ?? 'public'),
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

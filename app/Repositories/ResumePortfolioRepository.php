<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\ResumePortfolioRepositoryInterface;

final class ResumePortfolioRepository extends BaseRepository implements ResumePortfolioRepositoryInterface
{
    protected string $table = 'resume_portfolios';

    private const SELECT_JOINS = 'SELECT p.*,
                pr.`title` AS `project_title`,
                c.`name` AS `country_name`,
                ci.`name` AS `city_name`
             FROM `resume_portfolios` p
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
                p.`title` LIKE :q1 OR p.`description` LIKE :q2 OR p.`category` LIKE :q3
                OR p.`portfolio_url` LIKE :q4 OR p.`github_url` LIKE :q5 OR p.`behance_url` LIKE :q6
                OR p.`dribbble_url` LIKE :q7 OR p.`figma_url` LIKE :q8 OR p.`youtube_url` LIKE :q9
                OR p.`google_drive_url` LIKE :q10
            )';
            $like = '%' . $q . '%';
            foreach (range(1, 10) as $i) {
                $params['q' . $i] = $like;
            }
        }

        if (!empty($filters['category'])) {
            $where[] = 'p.`category` = :category';
            $params['category'] = (string) $filters['category'];
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
            'SELECT COUNT(*) FROM `resume_portfolios` p WHERE ' . $whereSql,
            $params
        )->fetchColumn();

        $items = $this->fetchAll(
            self::SELECT_JOINS . ' WHERE ' . $whereSql . ' ' . $order . ' LIMIT ' . $perPage . ' OFFSET ' . $offset,
            $params
        );

        return ['items' => $this->attachGalleries($items), 'total' => $total];
    }

    public function listPublicByResumeId(int $resumeId): array
    {
        if ($resumeId < 1) {
            return [];
        }

        $items = $this->fetchAll(
            self::SELECT_JOINS . '
             WHERE p.`resume_id` = :resume_id
               AND p.`deleted_at` IS NULL
               AND p.`visibility` = \'public\'
               AND p.`status` = \'active\'
             ORDER BY p.`is_featured` DESC, p.`sort_order` ASC, p.`id` DESC',
            ['resume_id' => $resumeId]
        );

        return $this->attachGalleries($items);
    }

    public function listForEmployerByResumeId(int $resumeId): array
    {
        if ($resumeId < 1) {
            return [];
        }

        $items = $this->fetchAll(
            self::SELECT_JOINS . '
             WHERE p.`resume_id` = :resume_id
               AND p.`deleted_at` IS NULL
               AND p.`visibility` IN (\'public\', \'employers\')
               AND p.`status` = \'active\'
             ORDER BY p.`is_featured` DESC, p.`sort_order` ASC, p.`id` DESC',
            ['resume_id' => $resumeId]
        );

        return $this->attachGalleries($items);
    }

    public function listDeletedByResumeId(int $resumeId): array
    {
        if ($resumeId < 1) {
            return [];
        }

        $items = $this->fetchAll(
            'SELECT p.*, pr.`title` AS `project_title`, c.`name` AS `country_name`, ci.`name` AS `city_name`
             FROM `resume_portfolios` p
             LEFT JOIN `resume_projects` pr ON pr.`id` = p.`project_id`
             LEFT JOIN `countries` c ON c.`id` = p.`country_id`
             LEFT JOIN `cities` ci ON ci.`id` = p.`city_id`
             WHERE p.`resume_id` = :resume_id AND p.`deleted_at` IS NOT NULL
             ORDER BY p.`deleted_at` DESC, p.`id` DESC',
            ['resume_id' => $resumeId]
        );

        return $this->attachGalleries($items);
    }

    public function search(int $resumeId, string $query, int $limit = 20): array
    {
        $result = $this->listByResumeId($resumeId, ['q' => $query, 'sort' => 'featured'], 1, max(1, min(50, $limit)));

        return $result['items'];
    }

    public function findOwned(int $id, int $resumeId): ?array
    {
        $row = $this->fetchOne(
            self::SELECT_JOINS . '
             WHERE p.`id` = :id AND p.`resume_id` = :resume_id AND p.`deleted_at` IS NULL
             LIMIT 1',
            ['id' => $id, 'resume_id' => $resumeId]
        );

        if ($row === null) {
            return null;
        }

        $row['gallery_images'] = $this->listGallery($id);

        return $row;
    }

    public function findDeletedOwned(int $id, int $resumeId): ?array
    {
        return $this->fetchOne(
            'SELECT p.*, pr.`title` AS `project_title`, c.`name` AS `country_name`, ci.`name` AS `city_name`
             FROM `resume_portfolios` p
             LEFT JOIN `resume_projects` pr ON pr.`id` = p.`project_id`
             LEFT JOIN `countries` c ON c.`id` = p.`country_id`
             LEFT JOIN `cities` ci ON ci.`id` = p.`city_id`
             WHERE p.`id` = :id AND p.`resume_id` = :resume_id AND p.`deleted_at` IS NOT NULL
             LIMIT 1',
            ['id' => $id, 'resume_id' => $resumeId]
        );
    }

    public function findDuplicate(int $resumeId, string $title, string $category, ?int $exceptId = null): ?array
    {
        $sql = 'SELECT `id`, `title`, `category`
                FROM `resume_portfolios`
                WHERE `resume_id` = :resume_id AND `deleted_at` IS NULL
                  AND LOWER(TRIM(`title`)) = LOWER(TRIM(:title))
                  AND `category` = :category';
        $params = [
            'resume_id' => $resumeId,
            'title' => $title,
            'category' => $category,
        ];

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
            'INSERT INTO `resume_portfolios`
                (`resume_id`, `project_id`, `country_id`, `city_id`, `title`, `category`, `description`,
                 `portfolio_url`, `github_url`, `behance_url`, `dribbble_url`, `figma_url`, `youtube_url`,
                 `google_drive_url`, `featured_image_path`, `featured_image_original_name`,
                 `featured_image_mime`, `featured_image_size`, `is_featured`, `visibility`, `status`, `sort_order`)
             VALUES
                (:resume_id, :project_id, :country_id, :city_id, :title, :category, :description,
                 :portfolio_url, :github_url, :behance_url, :dribbble_url, :figma_url, :youtube_url,
                 :google_drive_url, :featured_image_path, :featured_image_original_name,
                 :featured_image_mime, :featured_image_size, :is_featured, :visibility, :status, :sort_order)',
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
            $payload['featured_image_path'],
            $payload['featured_image_original_name'],
            $payload['featured_image_mime'],
            $payload['featured_image_size']
        );
        $payload['id'] = $id;

        $this->query(
            'UPDATE `resume_portfolios` SET
                `project_id` = :project_id,
                `country_id` = :country_id,
                `city_id` = :city_id,
                `title` = :title,
                `category` = :category,
                `description` = :description,
                `portfolio_url` = :portfolio_url,
                `github_url` = :github_url,
                `behance_url` = :behance_url,
                `dribbble_url` = :dribbble_url,
                `figma_url` = :figma_url,
                `youtube_url` = :youtube_url,
                `google_drive_url` = :google_drive_url,
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
            'UPDATE `resume_portfolios`
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
            'UPDATE `resume_portfolios`
             SET `deleted_at` = NULL, `status` = \'active\'
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NOT NULL',
            ['id' => $id, 'resume_id' => $resumeId]
        );

        return true;
    }

    public function updateFeaturedImageMeta(int $id, int $resumeId, array $meta): bool
    {
        if ($this->findOwned($id, $resumeId) === null) {
            return false;
        }

        $this->query(
            'UPDATE `resume_portfolios` SET
                `featured_image_path` = :path,
                `featured_image_original_name` = :original_name,
                `featured_image_mime` = :mime,
                `featured_image_size` = :size
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
                'UPDATE `resume_portfolios` SET `sort_order` = :sort_order
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
            'SELECT COUNT(*) FROM `resume_portfolios`
             WHERE `resume_id` = :resume_id AND `deleted_at` IS NULL',
            ['resume_id' => $resumeId]
        )->fetchColumn();
    }

    public function listGallery(int $portfolioId): array
    {
        if ($portfolioId < 1) {
            return [];
        }

        return $this->fetchAll(
            'SELECT `id`, `portfolio_id`, `image_path`, `original_name`, `mime`, `file_size`, `sort_order`,
                    `created_at`, `updated_at`, `deleted_at`
             FROM `resume_portfolio_gallery`
             WHERE `portfolio_id` = :portfolio_id AND `deleted_at` IS NULL
             ORDER BY `sort_order` ASC, `id` ASC',
            ['portfolio_id' => $portfolioId]
        );
    }

    public function addGalleryImage(int $portfolioId, array $data): int
    {
        $sortOrder = isset($data['sort_order']) ? (int) $data['sort_order'] : $this->countGallery($portfolioId);

        $this->query(
            'INSERT INTO `resume_portfolio_gallery`
                (`portfolio_id`, `image_path`, `original_name`, `mime`, `file_size`, `sort_order`)
             VALUES
                (:portfolio_id, :image_path, :original_name, :mime, :file_size, :sort_order)',
            [
                'portfolio_id' => $portfolioId,
                'image_path' => (string) $data['image_path'],
                'original_name' => $data['original_name'] ?? null,
                'mime' => $data['mime'] ?? null,
                'file_size' => isset($data['file_size']) && $data['file_size'] !== null
                    ? (int) $data['file_size']
                    : null,
                'sort_order' => $sortOrder,
            ]
        );

        return (int) $this->pdo->lastInsertId();
    }

    public function softDeleteGalleryImage(int $imageId, int $portfolioId): bool
    {
        $this->query(
            'UPDATE `resume_portfolio_gallery`
             SET `deleted_at` = CURRENT_TIMESTAMP(3)
             WHERE `id` = :id AND `portfolio_id` = :portfolio_id AND `deleted_at` IS NULL',
            ['id' => $imageId, 'portfolio_id' => $portfolioId]
        );

        return true;
    }

    public function findGalleryOwned(int $imageId, int $portfolioId, int $resumeId): ?array
    {
        return $this->fetchOne(
            'SELECT g.*
             FROM `resume_portfolio_gallery` g
             INNER JOIN `resume_portfolios` p ON p.`id` = g.`portfolio_id`
             WHERE g.`id` = :image_id
               AND g.`portfolio_id` = :portfolio_id
               AND p.`resume_id` = :resume_id
               AND g.`deleted_at` IS NULL
               AND p.`deleted_at` IS NULL
             LIMIT 1',
            [
                'image_id' => $imageId,
                'portfolio_id' => $portfolioId,
                'resume_id' => $resumeId,
            ]
        );
    }

    public function countGallery(int $portfolioId): int
    {
        if ($portfolioId < 1) {
            return 0;
        }

        return (int) $this->query(
            'SELECT COUNT(*) FROM `resume_portfolio_gallery`
             WHERE `portfolio_id` = :portfolio_id AND `deleted_at` IS NULL',
            ['portfolio_id' => $portfolioId]
        )->fetchColumn();
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function attachGalleries(array $items): array
    {
        if ($items === []) {
            return [];
        }

        $ids = [];
        foreach ($items as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        if ($ids === []) {
            return $items;
        }

        $placeholders = [];
        $params = [];
        foreach ($ids as $i => $id) {
            $key = 'pid' . $i;
            $placeholders[] = ':' . $key;
            $params[$key] = $id;
        }

        $galleryRows = $this->fetchAll(
            'SELECT `id`, `portfolio_id`, `image_path`, `original_name`, `mime`, `file_size`, `sort_order`,
                    `created_at`, `updated_at`, `deleted_at`
             FROM `resume_portfolio_gallery`
             WHERE `portfolio_id` IN (' . implode(', ', $placeholders) . ')
               AND `deleted_at` IS NULL
             ORDER BY `sort_order` ASC, `id` ASC',
            $params
        );

        $byPortfolio = [];
        foreach ($galleryRows as $gallery) {
            $pid = (int) $gallery['portfolio_id'];
            $byPortfolio[$pid][] = $gallery;
        }

        foreach ($items as &$item) {
            $pid = (int) ($item['id'] ?? 0);
            $item['gallery_images'] = $byPortfolio[$pid] ?? [];
        }
        unset($item);

        return $items;
    }

    private function orderSql(string $sort): string
    {
        return match ($sort) {
            'newest' => 'ORDER BY p.`created_at` DESC, p.`id` DESC',
            'oldest' => 'ORDER BY p.`created_at` ASC, p.`id` ASC',
            'title' => 'ORDER BY p.`title` ASC, p.`id` DESC',
            'featured' => 'ORDER BY p.`is_featured` DESC, p.`sort_order` ASC, p.`id` DESC',
            default => 'ORDER BY p.`is_featured` DESC, p.`sort_order` ASC, p.`id` DESC',
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
            'category' => $data['category'],
            'description' => $data['description'] ?? null,
            'portfolio_url' => $data['portfolio_url'] ?? null,
            'github_url' => $data['github_url'] ?? null,
            'behance_url' => $data['behance_url'] ?? null,
            'dribbble_url' => $data['dribbble_url'] ?? null,
            'figma_url' => $data['figma_url'] ?? null,
            'youtube_url' => $data['youtube_url'] ?? null,
            'google_drive_url' => $data['google_drive_url'] ?? null,
            'featured_image_path' => $data['featured_image_path'] ?? null,
            'featured_image_original_name' => $data['featured_image_original_name'] ?? null,
            'featured_image_mime' => $data['featured_image_mime'] ?? null,
            'featured_image_size' => isset($data['featured_image_size']) && $data['featured_image_size'] !== null
                ? (int) $data['featured_image_size']
                : null,
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

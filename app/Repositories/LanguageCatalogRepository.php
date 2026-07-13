<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Cache\CacheInterface;
use JobVisa\App\Repositories\Contracts\LanguageCatalogRepositoryInterface;
use PDO;

final class LanguageCatalogRepository extends BaseRepository implements LanguageCatalogRepositoryInterface
{
    protected string $table = 'languages';

    public function __construct(
        PDO $pdo,
        private readonly ?CacheInterface $cache = null,
    ) {
        parent::__construct($pdo);
    }

    public function listActive(): array
    {
        $loader = fn (): array => $this->fetchAll(
            'SELECT `id`, `name`, `code` FROM `languages` WHERE `is_active` = 1 ORDER BY `name` ASC'
        );

        if ($this->cache === null) {
            return $loader();
        }

        /** @var list<array<string, mixed>> $rows */
        $rows = $this->cache->remember(
            'catalog.languages.active',
            (int) config('performance.catalog_cache_ttl', 3600),
            $loader
        );

        return is_array($rows) ? $rows : [];
    }

    public function search(string $query, int $limit = 15): array
    {
        $query = trim($query);
        $limit = max(1, min(50, $limit));

        if ($query === '') {
            return array_slice($this->listActive(), 0, $limit);
        }

        return $this->fetchAll(
            'SELECT `id`, `name`, `code` FROM `languages`
             WHERE `is_active` = 1
               AND (`name` LIKE :q OR `code` LIKE :q2)
             ORDER BY
               CASE WHEN LOWER(`name`) = LOWER(:exact) THEN 0
                    WHEN LOWER(`name`) LIKE LOWER(:prefix) THEN 1
                    ELSE 2 END,
               `name` ASC
             LIMIT ' . $limit,
            [
                'q' => '%' . $query . '%',
                'q2' => '%' . $query . '%',
                'exact' => $query,
                'prefix' => $query . '%',
            ]
        );
    }

    public function isActive(int $languageId): bool
    {
        if ($languageId < 1) {
            return false;
        }

        return (bool) $this->query(
            'SELECT 1 FROM `languages` WHERE `id` = :id AND `is_active` = 1 LIMIT 1',
            ['id' => $languageId]
        )->fetchColumn();
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM `languages` WHERE `id` = :id LIMIT 1',
            ['id' => $id]
        );
    }
}

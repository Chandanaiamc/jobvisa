<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Cache\CacheInterface;
use JobVisa\App\Repositories\Contracts\SkillCatalogRepositoryInterface;
use PDO;

final class SkillCatalogRepository extends BaseRepository implements SkillCatalogRepositoryInterface
{
    protected string $table = 'skills';

    public function __construct(
        PDO $pdo,
        private readonly ?CacheInterface $cache = null,
    ) {
        parent::__construct($pdo);
    }

    public function listActive(): array
    {
        $loader = fn (): array => $this->fetchAll(
            'SELECT `id`, `name`, `slug` FROM `skills` WHERE `is_active` = 1 ORDER BY `name` ASC'
        );

        if ($this->cache === null) {
            return $loader();
        }

        /** @var list<array<string, mixed>> $rows */
        $rows = $this->cache->remember(
            'catalog.skills.active',
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
            'SELECT `id`, `name`, `slug` FROM `skills`
             WHERE `is_active` = 1
               AND (`name` LIKE :q OR `slug` LIKE :q2)
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

    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM `skills` WHERE `id` = :id LIMIT 1',
            ['id' => $id]
        );
    }

    public function isActive(int $skillId): bool
    {
        if ($skillId < 1) {
            return false;
        }

        return (bool) $this->query(
            'SELECT 1 FROM `skills` WHERE `id` = :id AND `is_active` = 1 LIMIT 1',
            ['id' => $skillId]
        )->fetchColumn();
    }

    public function findOrCreateCustom(string $name): int
    {
        $name = trim($name);
        $slug = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
        $slug = trim($slug, '-') ?: ('custom-' . bin2hex(random_bytes(4)));

        $existing = $this->fetchOne(
            'SELECT `id` FROM `skills` WHERE `slug` = :slug OR LOWER(`name`) = LOWER(:name) LIMIT 1',
            ['slug' => $slug, 'name' => $name]
        );

        if ($existing !== null) {
            return (int) $existing['id'];
        }

        $this->query(
            'INSERT INTO `skills` (`name`, `slug`, `is_active`) VALUES (:name, :slug, 1)',
            ['name' => $name, 'slug' => $slug]
        );

        $this->cache?->forget('catalog.skills.active');

        return (int) $this->pdo->lastInsertId();
    }
}

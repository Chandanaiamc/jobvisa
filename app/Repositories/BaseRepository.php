<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use PDO;
use PDOStatement;

/**
 * Shared PDO helpers for enterprise repositories (SOLID — ISP/DIP friendly base).
 *
 * Does not encode domain rules; subclasses own table-specific queries.
 */
abstract class BaseRepository
{
    protected string $table;

    protected string $primaryKey = 'id';

    public function __construct(
        protected readonly PDO $pdo
    ) {
    }

    /**
     * @param  array<int|string, mixed>  $params
     */
    protected function query(string $sql, array $params = []): PDOStatement
    {
        return \JobVisa\App\Domain\Performance\Services\QueryProfiler::observe(
            $sql,
            function () use ($sql, $params): PDOStatement {
                $statement = $this->pdo->prepare($sql);
                $statement->execute($params);

                return $statement;
            }
        );
    }

    /**
     * @param  array<int|string, mixed>  $params
     * @return array<string, mixed>|null
     */
    protected function fetchOne(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    /**
     * @param  array<int|string, mixed>  $params
     * @return list<array<string, mixed>>
     */
    protected function fetchAll(string $sql, array $params = []): array
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = $this->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function findRowById(int|string $id): ?array
    {
        $sql = sprintf(
            'SELECT * FROM `%s` WHERE `%s` = :id LIMIT 1',
            $this->table,
            $this->primaryKey
        );

        return $this->fetchOne($sql, ['id' => $id]);
    }

    protected function rowExists(int|string $id): bool
    {
        $sql = sprintf(
            'SELECT 1 FROM `%s` WHERE `%s` = :id LIMIT 1',
            $this->table,
            $this->primaryKey
        );

        return (bool) $this->query($sql, ['id' => $id])->fetchColumn();
    }
}

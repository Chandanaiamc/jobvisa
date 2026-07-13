<?php

declare(strict_types=1);

namespace JobVisa\App\Database\Seeders\Support;

use JobVisa\App\Database\Seeders\Contracts\SeederInterface;
use PDO;
use PDOException;
use RuntimeException;

/**
 * Base seeder with idempotent helpers (no business-domain logic).
 */
abstract class Seeder implements SeederInterface
{
    public function __construct(
        protected readonly PDO $pdo
    ) {
    }

    abstract public function name(): string;

    abstract public function run(): void;

    protected function tableExists(string $table): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT 1
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table
             LIMIT 1'
        );
        $statement->execute(['table' => $table]);

        return (bool) $statement->fetchColumn();
    }

    protected function columnExists(string $table, string $column): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT 1
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table
               AND COLUMN_NAME = :column
             LIMIT 1'
        );
        $statement->execute([
            'table' => $table,
            'column' => $column,
        ]);

        return (bool) $statement->fetchColumn();
    }

    /**
     * Insert a row when the unique key is missing; optionally refresh non-key columns.
     *
     * @param  array<string, mixed>  $unique  Column => value used to detect existing row
     * @param  array<string, mixed>  $values  Full column set for insert / update
     * @param  list<string>  $updateColumns  Columns to refresh on conflict (empty = insert-only)
     */
    protected function upsertByUnique(
        string $table,
        array $unique,
        array $values,
        array $updateColumns = []
    ): void {
        if ($unique === []) {
            throw new RuntimeException('upsertByUnique requires at least one unique column.');
        }

        $where = [];
        $params = [];

        foreach ($unique as $column => $value) {
            $where[] = "`{$column}` = :u_{$column}";
            $params["u_{$column}"] = $value;
        }

        $sql = sprintf(
            'SELECT `id` FROM `%s` WHERE %s LIMIT 1',
            $table,
            implode(' AND ', $where)
        );

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        $existingId = $statement->fetchColumn();

        if ($existingId !== false) {
            if ($updateColumns === []) {
                return;
            }

            $sets = [];
            $updateParams = ['id' => $existingId];

            foreach ($updateColumns as $column) {
                if (!array_key_exists($column, $values)) {
                    continue;
                }

                $sets[] = "`{$column}` = :{$column}";
                $updateParams[$column] = $values[$column];
            }

            if ($sets === []) {
                return;
            }

            $updateSql = sprintf(
                'UPDATE `%s` SET %s WHERE `id` = :id',
                $table,
                implode(', ', $sets)
            );

            $this->pdo->prepare($updateSql)->execute($updateParams);

            return;
        }

        $columns = array_keys($values);
        $placeholders = array_map(static fn (string $c): string => ':' . $c, $columns);

        $insertSql = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES (%s)',
            $table,
            implode('`, `', $columns),
            implode(', ', $placeholders)
        );

        try {
            $this->pdo->prepare($insertSql)->execute($values);
        } catch (PDOException $exception) {
            // Race / duplicate key: treat as success for idempotency.
            if ((int) $exception->getCode() === 23000 || str_contains($exception->getMessage(), 'Duplicate')) {
                return;
            }

            throw $exception;
        }
    }

    /**
     * @return int|null Primary key when found
     */
    protected function findIdBy(string $table, string $column, mixed $value): ?int
    {
        $sql = sprintf('SELECT `id` FROM `%s` WHERE `%s` = :value LIMIT 1', $table, $column);
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['value' => $value]);
        $id = $statement->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    protected function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}

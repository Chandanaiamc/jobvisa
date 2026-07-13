<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;

/**
 * Base model for all application models.
 *
 * Every future model must extend this class.
 * Provides shared PDO access via the Database singleton.
 */
abstract class BaseModel extends Model
{
    /**
     * Get the shared PDO connection.
     */
    protected static function db(): PDO
    {
        return Database::connection();
    }

    /**
     * Run a prepared statement and return the PDOStatement.
     *
     * @param  array<int|string, mixed>  $params
     */
    protected static function query(string $sql, array $params = []): PDOStatement
    {
        return Database::query($sql, $params);
    }

    /**
     * Fetch all rows for a prepared query.
     *
     * @param  array<int|string, mixed>  $params
     * @return array<int, array<string, mixed>>
     */
    protected static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    /**
     * Fetch a single row for a prepared query.
     *
     * @param  array<int|string, mixed>  $params
     * @return array<string, mixed>|null
     */
    protected static function fetchOne(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();

        return $row === false ? null : $row;
    }
}

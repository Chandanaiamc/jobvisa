<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * PDO database connection manager (Singleton).
 *
 * Credentials are read from environment-backed config.
 * Connections use exception mode and native prepared statements.
 */
final class Database
{
    private static ?self $instance = null;

    private PDO $pdo;

    private function __construct()
    {
        $host = (string) config('app.db.host', 'localhost');
        $port = (string) config('app.db.port', '3306');
        $name = (string) config('app.db.name', 'jobvisa_db');
        $user = (string) config('app.db.user', 'root');
        $password = (string) config('app.db.password', '');

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);

        try {
            $this->pdo = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException(
                'Unable to connect to the database: ' . $exception->getMessage(),
                (int) $exception->getCode(),
                $exception
            );
        }
    }

    /**
     * Get the singleton Database instance.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Get the shared PDO connection.
     */
    public static function connection(): PDO
    {
        return self::getInstance()->pdo;
    }

    /**
     * Prepare and execute a statement with bound parameters.
     *
     * @param  array<int|string, mixed>  $params
     */
    public static function query(string $sql, array $params = []): \PDOStatement
    {
        return \JobVisa\App\Domain\Performance\Services\QueryProfiler::observe(
            $sql,
            static function () use ($sql, $params): \PDOStatement {
                $statement = self::connection()->prepare($sql);
                $statement->execute($params);

                return $statement;
            }
        );
    }

    /**
     * Reset the singleton (useful for tests / reconnect).
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    private function __clone(): void
    {
    }

    public function __wakeup(): void
    {
        throw new RuntimeException('Cannot unserialize a singleton Database instance.');
    }
}

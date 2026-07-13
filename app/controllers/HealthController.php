<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use Throwable;

/**
 * System health checks.
 */
final class HealthController extends Controller
{
    /**
     * Verify the PDO database connection.
     */
    public function database(): void
    {
        $databaseName = (string) config('app.db.name', 'jobvisa_db');

        try {
            Database::connection();

            $mysqlVersion = (string) Database::query('SELECT VERSION()')->fetchColumn();

            $this->render('health/database', [
                'title' => 'Database Health',
                'success' => true,
                'databaseName' => $databaseName,
                'phpVersion' => PHP_VERSION,
                'mysqlVersion' => $mysqlVersion,
                'errorMessage' => null,
            ]);
        } catch (Throwable $exception) {
            http_response_code(500);

            $message = $exception->getMessage();

            // Never expose credentials if they appear in connection strings.
            $message = preg_replace('/password=[^;\s]*/i', 'password=***', $message) ?? $message;

            if (!config('app.debug', false)) {
                $message = 'Database connection failed. Please contact the system administrator.';
            }

            $this->render('health/database', [
                'title' => 'Database Health',
                'success' => false,
                'databaseName' => $databaseName,
                'phpVersion' => PHP_VERSION,
                'mysqlVersion' => null,
                'errorMessage' => $message,
            ]);
        }
    }
}

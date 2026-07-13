<?php

declare(strict_types=1);

namespace JobVisa\App\Providers;

use App\Core\Database;

/**
 * Database access bindings (no schema changes).
 *
 * Connection remains lazy — only established when Database is resolved.
 */
final class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(Database::class, static function (): Database {
            return Database::getInstance();
        });
    }

    public function boot(): void
    {
        // Intentionally empty — avoid connecting on every request.
    }
}

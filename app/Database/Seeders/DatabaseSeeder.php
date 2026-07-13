<?php

declare(strict_types=1);

namespace JobVisa\App\Database\Seeders;

use JobVisa\App\Database\Seeders\Contracts\SeederInterface;
use JobVisa\App\Database\Seeders\Support\SeederRunner;
use PDO;

/**
 * Orchestrates the full configured seeder stack.
 */
final class DatabaseSeeder implements SeederInterface
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function name(): string
    {
        return 'Database';
    }

    public function run(): void
    {
        /** @var array{order?: list<class-string<SeederInterface>>} $config */
        $config = config('seeders', []);
        $order = $config['order'] ?? [];

        $runner = new SeederRunner($this->pdo, $order);
        $runner->run();
    }
}

<?php

declare(strict_types=1);

namespace JobVisa\App\Database\Seeders\Contracts;

/**
 * Contract for a single modular database seeder.
 */
interface SeederInterface
{
    /**
     * Human-readable seeder name for CLI output.
     */
    public function name(): string;

    /**
     * Run the seeder (must be safe to execute multiple times).
     */
    public function run(): void;
}

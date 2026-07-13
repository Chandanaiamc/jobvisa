<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories\Contracts;

/**
 * Infrastructure resume persistence (Sprint 2C CV + 2D.1 multi-resume).
 */
interface ResumeRepositoryInterface
{
    /** @return array<string, mixed>|null */
    public function findPrimaryByUserId(int $userId): ?array;

    /** @return array<string, mixed> */
    public function ensurePrimary(int $userId, string $title = 'Primary CV'): array;

    public function updateFile(int $resumeId, ?string $path, ?string $mime, ?int $sizeBytes): void;

    public function updateCompleteness(int $resumeId, int $score): void;

    /** @return array<string, mixed>|null */
    public function findByIdForUser(int $resumeId, int $userId): ?array;

    /** @return array<string, mixed>|null */
    public function findRecordById(int $resumeId): ?array;

    /** @return list<array<string, mixed>> */
    public function listActiveRecordsForUser(int $userId): array;
}

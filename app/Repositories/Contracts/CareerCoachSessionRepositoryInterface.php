<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories\Contracts;

interface CareerCoachSessionRepositoryInterface
{
    /** @return array<string, mixed>|null */
    public function findByResumeId(int $resumeId): ?array;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function upsert(int $resumeId, int $userId, array $payload): void;
}

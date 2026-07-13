<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories\Contracts;

interface ResumeProfessionalRepositoryInterface
{
    /** @return array<string, mixed>|null */
    public function findByResumeId(int $resumeId): ?array;

    /** @param array<string, mixed> $data */
    public function upsert(int $resumeId, array $data): void;
}

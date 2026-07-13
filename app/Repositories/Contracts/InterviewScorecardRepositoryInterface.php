<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories\Contracts;

interface InterviewScorecardRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function upsert(int $sessionId, array $payload): void;

    /** @return array<string, mixed>|null */
    public function findBySessionId(int $sessionId): ?array;
}

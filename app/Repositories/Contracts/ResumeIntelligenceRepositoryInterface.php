<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories\Contracts;

interface ResumeIntelligenceRepositoryInterface
{
    /** @return array<string, mixed>|null */
    public function findLatestByResumeId(int $resumeId): ?array;

    /**
     * Upsert snapshot for a resume.
     *
     * @param  array{
     *   overall_score: int,
     *   ats_score: int,
     *   employer_readiness_score: int,
     *   keyword_match_score: int,
     *   strength_level: string,
     *   score_breakdown: array<string, mixed>,
     *   recommendations: list<array<string, mixed>>,
     *   analysis_json: array<string, mixed>,
     *   rules_version: string,
     *   calculated_at: string
     * }  $payload
     */
    public function upsert(int $resumeId, array $payload): void;
}

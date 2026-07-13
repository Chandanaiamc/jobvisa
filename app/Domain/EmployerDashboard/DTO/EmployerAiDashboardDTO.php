<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\EmployerDashboard\DTO;

/**
 * Aggregated employer AI hiring dashboard payload.
 */
final class EmployerAiDashboardDTO
{
    /**
     * @param  array<string, mixed>  $health
     * @param  list<array<string, mixed>>  $topCandidates
     * @param  list<array<string, mixed>>  $interviewReady
     * @param  list<array{label: string, count: int}>  $skillGaps
     * @param  list<array{label: string, value: int|float}>  $chartMatchByJob
     * @param  list<array{label: string, value: int}>  $chartStatusMix
     * @param  list<array{label: string, value: int}>  $chartScoreBands
     * @param  list<string>  $insights
     * @param  list<array<string, mixed>>  $jobs
     */
    public function __construct(
        public readonly int $employerUserId,
        public readonly int $jobsCount,
        public readonly int $publishedJobsCount,
        public readonly int $applicantsCount,
        public readonly float $averageMatchScore,
        public readonly float $averageRankingScore,
        public readonly array $health,
        public readonly array $topCandidates,
        public readonly array $interviewReady,
        public readonly array $skillGaps,
        public readonly array $chartMatchByJob,
        public readonly array $chartStatusMix,
        public readonly array $chartScoreBands,
        public readonly array $insights,
        public readonly array $jobs,
        public readonly string $generatedAt,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'employer_user_id' => $this->employerUserId,
            'jobs_count' => $this->jobsCount,
            'published_jobs_count' => $this->publishedJobsCount,
            'applicants_count' => $this->applicantsCount,
            'average_match_score' => $this->averageMatchScore,
            'average_ranking_score' => $this->averageRankingScore,
            'health' => $this->health,
            'top_candidates' => $this->topCandidates,
            'interview_ready' => $this->interviewReady,
            'skill_gaps' => $this->skillGaps,
            'charts' => [
                'match_by_job' => $this->chartMatchByJob,
                'status_mix' => $this->chartStatusMix,
                'score_bands' => $this->chartScoreBands,
            ],
            'insights' => $this->insights,
            'jobs' => $this->jobs,
            'generated_at' => $this->generatedAt,
        ];
    }
}

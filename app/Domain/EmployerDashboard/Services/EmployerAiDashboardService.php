<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\EmployerDashboard\Services;

use JobVisa\App\Domain\ApplicantRanking\Services\ApplicantRankingService;
use JobVisa\App\Domain\EmployerDashboard\DTO\EmployerAiDashboardDTO;
use JobVisa\App\Domain\EmployerDashboard\Exceptions\EmployerDashboardException;
use JobVisa\App\Domain\EmployerDashboard\Policies\EmployerDashboardPolicy;
use JobVisa\App\Repositories\Contracts\ApplicationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\JobApplicantRankingRepositoryInterface;
use JobVisa\App\Repositories\Contracts\JobRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeJobMatchRepositoryInterface;

/**
 * Aggregates hiring insights from rankings, matches, and applications.
 * No external AI APIs — deterministic heuristics only.
 */
final class EmployerAiDashboardService
{
    public const INTERVIEW_READY_OVERALL = 70;
    public const INTERVIEW_READY_MATCH = 55;

    public function __construct(
        private readonly JobRepositoryInterface $jobs,
        private readonly ApplicationRepositoryInterface $applications,
        private readonly JobApplicantRankingRepositoryInterface $rankings,
        private readonly ResumeJobMatchRepositoryInterface $matches,
        private readonly ApplicantRankingService $rankingService,
        private readonly EmployerDashboardPolicy $policy,
    ) {
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    public function page(array $actor, bool $refreshRankings = false): array
    {
        if (!$this->policy->canView($actor)) {
            throw EmployerDashboardException::forbidden();
        }

        $userId = (int) ($actor['id'] ?? 0);
        $jobs = $this->jobs->listOwnedByEmployerUser($userId, 50);
        $jobIds = [];
        $published = 0;
        foreach ($jobs as $job) {
            $jid = (int) ($job['id'] ?? 0);
            if ($jid > 0) {
                $jobIds[] = $jid;
            }
            if ((string) ($job['status'] ?? '') === 'published') {
                $published++;
            }
        }

        if ($refreshRankings && $this->policy->canRefresh($actor)) {
            foreach ($jobIds as $jid) {
                if ((string) (($this->findJob($jobs, $jid)['status'] ?? '')) === 'published') {
                    try {
                        $this->rankingService->recalculate($actor, $jid);
                    } catch (\Throwable) {
                        // Continue aggregating remaining jobs
                    }
                }
            }
        }

        $rankedRows = $this->rankings->listByJobIds($jobIds, 500);
        $matchRows = $this->matches->listActiveByJobIds($jobIds, 500);
        $statusMix = $this->applications->countByStatusForJobIds($jobIds);
        $applicantsCount = array_sum($statusMix);

        $avgMatch = $this->average(array_map(
            static fn (array $r): int => (int) ($r['overall_score'] ?? 0),
            $matchRows
        ));
        $avgRank = $this->average(array_map(
            static fn (array $r): int => (int) ($r['overall_score'] ?? 0),
            $rankedRows
        ));

        $top = $this->mapCandidates(array_slice($rankedRows, 0, 8));
        $interviewReady = $this->mapCandidates(array_values(array_filter(
            $rankedRows,
            static function (array $r): bool {
                $status = (string) ($r['application_status'] ?? '');
                if (in_array($status, ['rejected', 'withdrawn'], true)) {
                    return false;
                }

                return (int) ($r['overall_score'] ?? 0) >= self::INTERVIEW_READY_OVERALL
                    && (int) ($r['job_match_score'] ?? 0) >= self::INTERVIEW_READY_MATCH;
            }
        )));
        $interviewReady = array_slice($interviewReady, 0, 8);

        $skillGaps = $this->aggregateSkillGaps($matchRows);
        $chartMatchByJob = $this->chartMatchByJob($jobs, $matchRows, $rankedRows);
        $chartStatus = [];
        foreach ($statusMix as $status => $cnt) {
            if ($status === '') {
                continue;
            }
            $chartStatus[] = ['label' => $status, 'value' => $cnt];
        }
        $chartBands = $this->scoreBands($rankedRows);

        $health = $this->hiringHealth(
            count($jobs),
            $published,
            $applicantsCount,
            count($interviewReady),
            $avgMatch,
            $avgRank,
            $statusMix
        );

        $insights = $this->insights($health, $skillGaps, $avgMatch, count($interviewReady), $applicantsCount, $published);

        $dto = new EmployerAiDashboardDTO(
            employerUserId: $userId,
            jobsCount: count($jobs),
            publishedJobsCount: $published,
            applicantsCount: $applicantsCount,
            averageMatchScore: $avgMatch,
            averageRankingScore: $avgRank,
            health: $health,
            topCandidates: $top,
            interviewReady: $interviewReady,
            skillGaps: $skillGaps,
            chartMatchByJob: $chartMatchByJob,
            chartStatusMix: $chartStatus,
            chartScoreBands: $chartBands,
            insights: $insights,
            jobs: array_map(static function (array $j): array {
                return [
                    'id' => (int) ($j['id'] ?? 0),
                    'title' => (string) ($j['title'] ?? ''),
                    'status' => (string) ($j['status'] ?? ''),
                    'applications_count' => (int) ($j['applications_count'] ?? 0),
                    'country_name' => (string) ($j['country_name'] ?? ''),
                ];
            }, $jobs),
            generatedAt: (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s'),
        );

        return [
            'dashboard' => $dto,
            'can_refresh' => $this->policy->canRefresh($actor),
            'disclaimer' => 'AI hiring insights are deterministic aggregates from resume scores, job match, and applicant rankings. They guide screening — they are not hiring decisions.',
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function refresh(array $actor): array
    {
        if (!$this->policy->canRefresh($actor)) {
            throw EmployerDashboardException::forbidden();
        }

        $this->page($actor, true);

        return [
            'success' => true,
            'message' => 'Employer AI dashboard insights refreshed.',
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $jobs
     * @return array<string, mixed>
     */
    private function findJob(array $jobs, int $jobId): array
    {
        foreach ($jobs as $job) {
            if ((int) ($job['id'] ?? 0) === $jobId) {
                return $job;
            }
        }

        return [];
    }

    /**
     * @param  list<int>  $values
     */
    private function average(array $values): float
    {
        if ($values === []) {
            return 0.0;
        }

        return round(array_sum($values) / count($values), 1);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function mapCandidates(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'application_id' => (int) ($row['application_id'] ?? 0),
                'job_id' => (int) ($row['job_id'] ?? 0),
                'job_title' => (string) ($row['job_title'] ?? ''),
                'applicant_name' => (string) ($row['applicant_name'] ?? 'Applicant'),
                'applicant_email' => (string) ($row['applicant_email'] ?? ''),
                'application_status' => (string) ($row['application_status'] ?? ''),
                'rank_position' => (int) ($row['rank_position'] ?? 0),
                'overall_score' => (int) ($row['overall_score'] ?? 0),
                'job_match_score' => (int) ($row['job_match_score'] ?? 0),
                'resume_score' => (int) ($row['resume_score'] ?? 0),
                'skills_score' => (int) ($row['skills_score'] ?? 0),
            ];
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $matchRows
     * @return list<array{label: string, count: int}>
     */
    private function aggregateSkillGaps(array $matchRows): array
    {
        $counts = [];
        foreach ($matchRows as $row) {
            $raw = $row['explanation_json'] ?? null;
            $expl = is_array($raw) ? $raw : (is_string($raw) ? json_decode($raw, true) : []);
            if (!is_array($expl)) {
                continue;
            }
            $missing = $expl['missing_required_skills'] ?? $expl['missing_required'] ?? [];
            if (!is_array($missing)) {
                continue;
            }
            foreach ($missing as $skill) {
                $label = mb_strtolower(trim((string) $skill));
                if ($label === '' || mb_strlen($label) < 2) {
                    continue;
                }
                $counts[$label] = ($counts[$label] ?? 0) + 1;
            }
        }

        arsort($counts);
        $out = [];
        foreach (array_slice($counts, 0, 10, true) as $label => $count) {
            $out[] = ['label' => $label, 'count' => (int) $count];
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $jobs
     * @param  list<array<string, mixed>>  $matchRows
     * @param  list<array<string, mixed>>  $rankedRows
     * @return list<array{label: string, value: float}>
     */
    private function chartMatchByJob(array $jobs, array $matchRows, array $rankedRows): array
    {
        $byJob = [];
        foreach ($matchRows as $row) {
            $jid = (int) ($row['job_id'] ?? 0);
            if ($jid < 1) {
                continue;
            }
            $byJob[$jid]['scores'][] = (int) ($row['overall_score'] ?? 0);
            $byJob[$jid]['title'] = (string) ($row['job_title'] ?? '');
        }
        foreach ($rankedRows as $row) {
            $jid = (int) ($row['job_id'] ?? 0);
            if ($jid < 1 || isset($byJob[$jid]['scores'])) {
                continue;
            }
            $byJob[$jid]['scores'][] = (int) ($row['job_match_score'] ?? 0);
            $byJob[$jid]['title'] = (string) ($row['job_title'] ?? '');
        }

        $out = [];
        foreach ($jobs as $job) {
            $jid = (int) ($job['id'] ?? 0);
            $title = (string) ($job['title'] ?? ('Job #' . $jid));
            $scores = $byJob[$jid]['scores'] ?? [];
            $out[] = [
                'label' => mb_strlen($title) > 28 ? mb_substr($title, 0, 25) . '…' : $title,
                'value' => $this->average($scores),
                'job_id' => $jid,
            ];
        }

        return array_slice($out, 0, 8);
    }

    /**
     * @param  list<array<string, mixed>>  $rankedRows
     * @return list<array{label: string, value: int}>
     */
    private function scoreBands(array $rankedRows): array
    {
        $bands = [
            '90–100' => 0,
            '70–89' => 0,
            '50–69' => 0,
            '30–49' => 0,
            '0–29' => 0,
        ];
        foreach ($rankedRows as $row) {
            $s = (int) ($row['overall_score'] ?? 0);
            if ($s >= 90) {
                $bands['90–100']++;
            } elseif ($s >= 70) {
                $bands['70–89']++;
            } elseif ($s >= 50) {
                $bands['50–69']++;
            } elseif ($s >= 30) {
                $bands['30–49']++;
            } else {
                $bands['0–29']++;
            }
        }

        $out = [];
        foreach ($bands as $label => $value) {
            $out[] = ['label' => $label, 'value' => $value];
        }

        return $out;
    }

    /**
     * @param  array<string, int>  $statusMix
     * @return array<string, mixed>
     */
    private function hiringHealth(
        int $jobsCount,
        int $published,
        int $applicants,
        int $interviewReady,
        float $avgMatch,
        float $avgRank,
        array $statusMix,
    ): array {
        $shortlisted = (int) ($statusMix['shortlisted'] ?? 0);
        $hired = (int) ($statusMix['hired'] ?? 0);
        $pipelineActive = (int) ($statusMix['submitted'] ?? 0)
            + (int) ($statusMix['reviewing'] ?? 0)
            + $shortlisted;

        $score = 0;
        if ($published > 0) {
            $score += 20;
        }
        if ($applicants >= 3) {
            $score += 20;
        } elseif ($applicants > 0) {
            $score += 10;
        }
        if ($avgMatch >= 60) {
            $score += 20;
        } elseif ($avgMatch >= 40) {
            $score += 12;
        }
        if ($interviewReady > 0) {
            $score += 20;
        }
        if ($pipelineActive > 0) {
            $score += 10;
        }
        if ($hired > 0 || $shortlisted > 0) {
            $score += 10;
        }
        $score = max(0, min(100, $score));

        $label = match (true) {
            $score >= 80 => 'Strong',
            $score >= 60 => 'Healthy',
            $score >= 40 => 'Developing',
            default => 'Needs attention',
        };

        return [
            'score' => $score,
            'label' => $label,
            'published_jobs' => $published,
            'total_jobs' => $jobsCount,
            'applicants' => $applicants,
            'interview_ready' => $interviewReady,
            'pipeline_active' => $pipelineActive,
            'shortlisted' => $shortlisted,
            'hired' => $hired,
            'average_match' => $avgMatch,
            'average_ranking' => $avgRank,
        ];
    }

    /**
     * @param  array<string, mixed>  $health
     * @param  list<array{label: string, count: int}>  $skillGaps
     * @return list<string>
     */
    private function insights(
        array $health,
        array $skillGaps,
        float $avgMatch,
        int $interviewReady,
        int $applicants,
        int $published,
    ): array {
        $out = [];
        $out[] = sprintf('Hiring health is %s (%d/100).', (string) $health['label'], (int) $health['score']);
        if ($published === 0) {
            $out[] = 'Publish at least one job to start receiving ranked applicants.';
        }
        if ($applicants === 0) {
            $out[] = 'No applications yet — share published vacancies to build a candidate pipeline.';
        } else {
            $out[] = sprintf('Average AI job match across applicants is %.1f/100.', $avgMatch);
            $out[] = sprintf('%d candidate(s) meet interview-ready thresholds (rank ≥%d and match ≥%d).', $interviewReady, self::INTERVIEW_READY_OVERALL, self::INTERVIEW_READY_MATCH);
        }
        if ($skillGaps !== []) {
            $top = array_slice(array_map(static fn (array $g): string => $g['label'], $skillGaps), 0, 3);
            $out[] = 'Most common skill gaps vs job requirements: ' . implode(', ', $top) . '.';
        } else {
            $out[] = 'No concentrated skill gaps detected across current match analyses.';
        }

        return $out;
    }
}

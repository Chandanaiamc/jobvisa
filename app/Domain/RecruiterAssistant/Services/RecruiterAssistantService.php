<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\RecruiterAssistant\Services;

use JobVisa\App\Domain\EmployerDashboard\Services\EmployerAiDashboardService;
use JobVisa\App\Domain\RecruiterAssistant\Exceptions\RecruiterAssistantException;
use JobVisa\App\Domain\RecruiterAssistant\Policies\RecruiterAssistantPolicy;
use JobVisa\App\Domain\RecruiterAssistant\Support\RecruiterAssistantVersion;
use JobVisa\App\Repositories\Contracts\JobRepositoryInterface;
use JobVisa\App\Repositories\Contracts\RecruiterCandidateSearchRepositoryInterface;
use JobVisa\App\Repositories\Contracts\RecruiterSearchHistoryRepositoryInterface;
use JobVisa\App\Repositories\Contracts\SkillCatalogRepositoryInterface;

/**
 * Employer AI Recruiter Assistant application service.
 */
final class RecruiterAssistantService
{
    public function __construct(
        private readonly JobRepositoryInterface $jobs,
        private readonly RecruiterCandidateSearchRepositoryInterface $searchRepo,
        private readonly RecruiterSearchHistoryRepositoryInterface $history,
        private readonly NaturalLanguageQueryParser $parser,
        private readonly RecruiterSuggestionService $suggestions,
        private readonly RecruiterAssistantPolicy $policy,
        private readonly SkillCatalogRepositoryInterface $skills,
        private readonly EmployerAiDashboardService $dashboard,
    ) {
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    public function page(array $actor, ?string $prefillQuery = null): array
    {
        $this->assertCanUse($actor);
        $userId = (int) $actor['id'];
        $jobs = $this->jobs->listOwnedByEmployerUser($userId, 50);

        $skillGaps = [];
        try {
            $dash = $this->dashboard->page($actor, false);
            $skillGaps = $dash['dashboard']->skillGaps ?? [];
        } catch (\Throwable) {
            $skillGaps = [];
        }

        $criteria = null;
        $results = [];
        $suggestions = $this->suggestions->suggest(
            $this->parser->parse($prefillQuery ?: 'top ranked candidates', $this->jobHints($jobs), []),
            [],
            $skillGaps
        );

        if ($prefillQuery !== null && trim($prefillQuery) !== '') {
            $pack = $this->runSearch($actor, $prefillQuery, false);
            $criteria = $pack['criteria'];
            $results = $pack['results'];
            $suggestions = $pack['suggestions'];
        }

        return [
            'jobs' => $jobs,
            'criteria' => $criteria,
            'results' => $results,
            'suggestions' => $suggestions,
            'history' => $this->history->listByEmployer($userId, 15),
            'version' => RecruiterAssistantVersion::CURRENT,
            'disclaimer' => 'Recruiter Assistant uses deterministic natural-language parsing and your existing rankings/matches. It does not call external AI APIs.',
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    public function search(array $actor, string $query): array
    {
        $this->assertCanUse($actor);
        $query = trim($query);
        if ($query === '') {
            throw RecruiterAssistantException::invalidQuery();
        }
        if (mb_strlen($query) > 500) {
            throw RecruiterAssistantException::invalidQuery('Search query is too long.');
        }

        return $this->runSearch($actor, $query, true);
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function softDeleteHistory(array $actor, int $historyId): array
    {
        $this->assertCanManageHistory($actor);
        $ok = $this->history->softDelete($historyId, (int) $actor['id']);

        return [
            'success' => $ok,
            'message' => $ok ? 'Search history entry removed.' : 'History entry not found.',
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function clearHistory(array $actor): array
    {
        $this->assertCanManageHistory($actor);
        $n = $this->history->softDeleteAllForEmployer((int) $actor['id']);

        return [
            'success' => true,
            'message' => $n > 0 ? sprintf('Cleared %d search history entries.', $n) : 'No history to clear.',
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    private function runSearch(array $actor, string $query, bool $persistHistory): array
    {
        $userId = (int) $actor['id'];
        $jobs = $this->jobs->listOwnedByEmployerUser($userId, 50);
        $jobIds = array_values(array_filter(array_map(
            static fn (array $j): int => (int) ($j['id'] ?? 0),
            $jobs
        )));

        $catalog = [];
        foreach ($this->skills->listActive() as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name !== '') {
                $catalog[] = $name;
            }
        }

        $criteria = $this->parser->parse($query, $this->jobHints($jobs), $catalog);
        $filters = $criteria->toArray();
        $results = $this->searchRepo->search($jobIds, $filters, 25);

        $skillGaps = [];
        try {
            $dash = $this->dashboard->page($actor, false);
            $skillGaps = $dash['dashboard']->skillGaps ?? [];
        } catch (\Throwable) {
            $skillGaps = [];
        }

        $suggestions = $this->suggestions->suggest($criteria, $results, $skillGaps);
        $recommended = array_slice($results, 0, 5);

        if ($persistHistory) {
            $this->history->append($userId, [
                'query_text' => $query,
                'parsed_filters' => $criteria->toArray(),
                'result_count' => count($results),
                'top_result_json' => $recommended,
                'suggestions_json' => $suggestions,
            ]);
        }

        return [
            'criteria' => $criteria,
            'results' => $results,
            'recommended' => $recommended,
            'suggestions' => $suggestions,
            'jobs' => $jobs,
            'history' => $this->history->listByEmployer($userId, 15),
            'version' => RecruiterAssistantVersion::CURRENT,
            'success' => true,
            'message' => sprintf('Found %d candidate(s).', count($results)),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $jobs
     * @return list<array{id: int, title: string}>
     */
    private function jobHints(array $jobs): array
    {
        $out = [];
        foreach ($jobs as $job) {
            $out[] = [
                'id' => (int) ($job['id'] ?? 0),
                'title' => (string) ($job['title'] ?? ''),
            ];
        }

        return $out;
    }

    /** @param array<string, mixed> $actor */
    private function assertCanUse(array $actor): void
    {
        if (!$this->policy->canUse($actor)) {
            throw RecruiterAssistantException::forbidden();
        }
    }

    /** @param array<string, mixed> $actor */
    private function assertCanManageHistory(array $actor): void
    {
        if (!$this->policy->canManageHistory($actor)) {
            throw RecruiterAssistantException::forbidden();
        }
    }
}

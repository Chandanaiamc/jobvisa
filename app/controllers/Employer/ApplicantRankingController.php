<?php

declare(strict_types=1);

namespace App\Controllers\Employer;

use JobVisa\App\Domain\ApplicantRanking\Exceptions\ApplicantRankingException;
use JobVisa\App\Domain\ApplicantRanking\Services\ApplicantRankingService;
use JobVisa\App\Security\SessionManager;

/**
 * Employer applicant ranking dashboard (Sprint 2F.4).
 */
final class ApplicantRankingController extends EmployerController
{
    private ApplicantRankingService $ranking;

    public function __construct()
    {
        parent::__construct();
        $this->ranking = container(ApplicantRankingService::class);
    }

    public function jobs(): void
    {
        $data = $this->ranking->jobsIndex($this->actor());
        $this->dashboard('employer/pages/jobs/index', [
            'title' => 'My Jobs',
            'activeNav' => 'jobs',
            'jobs' => $data['jobs'],
            'disclaimer' => $data['disclaimer'],
        ]);
    }

    public function show(string $job): void
    {
        $filters = [
            'status' => $_GET['status'] ?? 'all',
            'min_score' => $_GET['min_score'] ?? '',
            'sort' => $_GET['sort'] ?? 'rank',
            'dir' => $_GET['dir'] ?? 'desc',
            'top' => $_GET['top'] ?? 50,
            'q' => $_GET['q'] ?? '',
        ];

        try {
            $data = $this->ranking->rankingPage($this->actor(), (int) $job, $filters, false);
        } catch (ApplicantRankingException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/employer/jobs'));
        }

        $this->dashboard('employer/pages/jobs/ranking', [
            'title' => 'Applicant Ranking',
            'activeNav' => 'jobs',
            'job' => $data['job'],
            'filters' => $data['filters'],
            'candidates' => $data['candidates'],
            'totalRanked' => $data['total_ranked'],
            'totalFiltered' => $data['total_filtered'],
            'canRecalculate' => $data['can_recalculate'],
            'disclaimer' => $data['disclaimer'],
            'filterInput' => $filters,
        ]);
    }

    public function recalculate(string $job): void
    {
        try {
            $result = $this->ranking->recalculate($this->actor(), (int) $job);
        } catch (ApplicantRankingException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/employer/jobs'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect(
            '/employer/jobs/' . (int) $job . '/applicants/ranking',
            $type,
            $result['message']
        );
    }

    public function history(string $job): void
    {
        try {
            $data = $this->ranking->historyPage($this->actor(), (int) $job);
        } catch (ApplicantRankingException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/employer/jobs'));
        }

        $this->dashboard('employer/pages/jobs/ranking-history', [
            'title' => 'Ranking History',
            'activeNav' => 'jobs',
            'job' => $data['job'],
            'history' => $data['history'],
            'canManage' => $data['can_manage'],
        ]);
    }

    public function deleteHistory(string $job, string $historyId): void
    {
        try {
            $result = $this->ranking->softDeleteHistoryEntry($this->actor(), (int) $job, (int) $historyId);
        } catch (ApplicantRankingException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/employer/jobs'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect(
            '/employer/jobs/' . (int) $job . '/applicants/ranking/history',
            $type,
            $result['message']
        );
    }

    public function clearHistory(string $job): void
    {
        try {
            $result = $this->ranking->clearHistory($this->actor(), (int) $job);
        } catch (ApplicantRankingException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/employer/jobs'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect(
            '/employer/jobs/' . (int) $job . '/applicants/ranking/history',
            $type,
            $result['message']
        );
    }
}

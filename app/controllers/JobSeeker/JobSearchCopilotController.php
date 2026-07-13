<?php

declare(strict_types=1);

namespace App\Controllers\JobSeeker;

use JobVisa\App\Domain\JobSearchCopilot\Exceptions\JobSearchCopilotException;
use JobVisa\App\Domain\JobSearchCopilot\Services\JobSearchCopilotService;
use JobVisa\App\Security\SessionManager;

/**
 * AI Job Search Copilot (Sprint 3.8).
 */
final class JobSearchCopilotController extends JobSeekerController
{
    private JobSearchCopilotService $copilot;

    public function __construct()
    {
        parent::__construct();
        $this->copilot = container(JobSearchCopilotService::class);
    }

    public function show(string $id): void
    {
        try {
            $data = $this->copilot->page($this->actor(), (int) $id);
        } catch (JobSearchCopilotException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $this->dashboard('jobseeker/pages/resumes/job-search-copilot', [
            'title' => 'Job Search Copilot',
            'activeNav' => 'resumes',
            'resume' => $data['resume'],
            'completion' => $data['completion'],
            'copilotPlan' => $data['plan'],
            'defaultCareerGoal' => $data['default_career_goal'],
            'versions' => $data['versions'],
            'history' => $data['history'],
            'deletedHistory' => $data['deleted_history'],
            'canEdit' => $data['can_edit'],
            'version' => $data['version'],
            'disclaimer' => $data['disclaimer'],
            'resumeSection' => 'job-search-copilot',
        ]);
    }

    public function generate(string $id): void
    {
        $goal = isset($_POST['career_goal']) ? (string) $_POST['career_goal'] : null;

        try {
            $result = $this->copilot->generate($this->actor(), (int) $id, $goal);
        } catch (JobSearchCopilotException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/job-search-copilot'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/job-search-copilot',
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }

    public function recalculate(string $id): void
    {
        $goal = isset($_POST['career_goal']) ? (string) $_POST['career_goal'] : null;

        try {
            $result = $this->copilot->recalculate($this->actor(), (int) $id, $goal);
        } catch (JobSearchCopilotException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/job-search-copilot'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/job-search-copilot',
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }

    public function history(string $id): void
    {
        try {
            $data = $this->copilot->historyPage($this->actor(), (int) $id);
        } catch (JobSearchCopilotException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $this->dashboard('jobseeker/pages/resumes/job-search-copilot-history', [
            'title' => 'Job Search Copilot History',
            'activeNav' => 'resumes',
            'resume' => $data['resume'],
            'history' => $data['history'],
            'deletedHistory' => $data['deleted_history'],
            'versions' => $data['versions'],
            'version' => $data['version'],
            'resumeSection' => 'job-search-copilot',
        ]);
    }

    public function deleteHistory(string $id, string $historyId): void
    {
        try {
            $result = $this->copilot->softDeleteHistory($this->actor(), (int) $id, (int) $historyId);
        } catch (JobSearchCopilotException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/job-search-copilot/history'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/job-search-copilot/history',
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }

    public function restoreHistory(string $id, string $historyId): void
    {
        try {
            $result = $this->copilot->restoreHistory($this->actor(), (int) $id, (int) $historyId);
        } catch (JobSearchCopilotException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/job-search-copilot/history'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/job-search-copilot/history',
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }

    public function purgeHistory(string $id, string $historyId): void
    {
        try {
            $result = $this->copilot->purgeHistory($this->actor(), (int) $id, (int) $historyId);
        } catch (JobSearchCopilotException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/job-search-copilot/history'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/job-search-copilot/history',
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }

    public function clearHistory(string $id): void
    {
        try {
            $result = $this->copilot->clearHistory($this->actor(), (int) $id);
        } catch (JobSearchCopilotException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/job-search-copilot/history'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/job-search-copilot/history',
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }

    public function exportPdf(string $id, string $planId): void
    {
        try {
            $file = $this->copilot->exportPdf($this->actor(), (int) $id, (int) $planId);
        } catch (JobSearchCopilotException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/job-search-copilot'));
        }

        header('Content-Type: ' . $file['mime']);
        header('Content-Disposition: attachment; filename="' . $file['filename'] . '"');
        header('Content-Length: ' . (string) strlen($file['content']));
        header('Cache-Control: no-store');
        echo $file['content'];
        exit;
    }
}

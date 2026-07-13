<?php

declare(strict_types=1);

namespace App\Controllers\JobSeeker;

use JobVisa\App\Domain\MockInterview\Exceptions\MockInterviewException;
use JobVisa\App\Domain\MockInterview\Services\MockInterviewService;
use JobVisa\App\Security\SessionManager;

/**
 * AI Mock Interview Simulator (Sprint 3.7).
 */
final class MockInterviewController extends JobSeekerController
{
    private MockInterviewService $mockInterview;

    public function __construct()
    {
        parent::__construct();
        $this->mockInterview = container(MockInterviewService::class);
    }

    public function show(string $id): void
    {
        $jobId = isset($_GET['job_id']) ? (int) $_GET['job_id'] : null;

        try {
            $data = $this->mockInterview->page($this->actor(), (int) $id, $jobId);
        } catch (MockInterviewException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $this->dashboard('jobseeker/pages/resumes/mock-interview', [
            'title' => 'Mock Interview',
            'activeNav' => 'resumes',
            'resume' => $data['resume'],
            'completion' => $data['completion'],
            'matchedJobs' => $data['matched_jobs'],
            'selectedJobId' => $data['selected_job_id'],
            'mockSession' => $data['mock_session'],
            'versions' => $data['versions'],
            'history' => $data['history'],
            'deletedHistory' => $data['deleted_history'],
            'canEdit' => $data['can_edit'],
            'version' => $data['version'],
            'disclaimer' => $data['disclaimer'],
            'resumeSection' => 'mock-interview',
        ]);
    }

    public function generate(string $id): void
    {
        $jobId = (int) ($_POST['job_id'] ?? 0);

        try {
            $result = $this->mockInterview->generate($this->actor(), (int) $id, $jobId);
        } catch (MockInterviewException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/mock-interview'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/mock-interview',
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }

    public function analyze(string $id): void
    {
        $sessionId = (int) ($_POST['session_id'] ?? 0);
        $answers = is_array($_POST['answers'] ?? null) ? $_POST['answers'] : [];

        try {
            $result = $this->mockInterview->analyze($this->actor(), (int) $id, $sessionId, $answers);
        } catch (MockInterviewException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/mock-interview'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/mock-interview',
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }

    public function recalculate(string $id): void
    {
        $jobId = (int) ($_POST['job_id'] ?? 0);

        try {
            $result = $this->mockInterview->recalculate($this->actor(), (int) $id, $jobId);
        } catch (MockInterviewException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/mock-interview'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/mock-interview',
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }

    public function history(string $id): void
    {
        try {
            $data = $this->mockInterview->historyPage($this->actor(), (int) $id);
        } catch (MockInterviewException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $this->dashboard('jobseeker/pages/resumes/mock-interview-history', [
            'title' => 'Mock Interview History',
            'activeNav' => 'resumes',
            'resume' => $data['resume'],
            'history' => $data['history'],
            'deletedHistory' => $data['deleted_history'],
            'versions' => $data['versions'],
            'version' => $data['version'],
            'resumeSection' => 'mock-interview',
        ]);
    }

    public function deleteHistory(string $id, string $historyId): void
    {
        try {
            $result = $this->mockInterview->softDeleteHistory($this->actor(), (int) $id, (int) $historyId);
        } catch (MockInterviewException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/mock-interview/history'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/mock-interview/history',
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }

    public function restoreHistory(string $id, string $historyId): void
    {
        try {
            $result = $this->mockInterview->restoreHistory($this->actor(), (int) $id, (int) $historyId);
        } catch (MockInterviewException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/mock-interview/history'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/mock-interview/history',
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }

    public function purgeHistory(string $id, string $historyId): void
    {
        try {
            $result = $this->mockInterview->purgeHistory($this->actor(), (int) $id, (int) $historyId);
        } catch (MockInterviewException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/mock-interview/history'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/mock-interview/history',
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }

    public function clearHistory(string $id): void
    {
        try {
            $result = $this->mockInterview->clearHistory($this->actor(), (int) $id);
        } catch (MockInterviewException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/mock-interview/history'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/mock-interview/history',
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }

    public function exportPdf(string $id, string $sessionId): void
    {
        try {
            $file = $this->mockInterview->exportPdf($this->actor(), (int) $id, (int) $sessionId);
        } catch (MockInterviewException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/mock-interview'));
        }

        header('Content-Type: ' . $file['mime']);
        header('Content-Disposition: attachment; filename="' . $file['filename'] . '"');
        header('Content-Length: ' . (string) strlen($file['content']));
        header('Cache-Control: no-store');
        echo $file['content'];
        exit;
    }
}

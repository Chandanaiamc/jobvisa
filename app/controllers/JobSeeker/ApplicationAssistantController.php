<?php

declare(strict_types=1);

namespace App\Controllers\JobSeeker;

use JobVisa\App\Domain\ApplicationAssistant\Exceptions\ApplicationAssistantException;
use JobVisa\App\Domain\ApplicationAssistant\Services\ApplicationAssistantService;
use JobVisa\App\Security\SessionManager;

/**
 * AI Application Assistant (Sprint 3.2).
 */
final class ApplicationAssistantController extends JobSeekerController
{
    private ApplicationAssistantService $assistant;

    public function __construct()
    {
        parent::__construct();
        $this->assistant = container(ApplicationAssistantService::class);
    }

    public function show(string $job): void
    {
        $resumeId = isset($_GET['resume']) ? (int) $_GET['resume'] : null;
        if ($resumeId !== null && $resumeId < 1) {
            $resumeId = null;
        }

        try {
            $data = $this->assistant->page($this->actor(), (int) $job, $resumeId);
        } catch (ApplicationAssistantException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker'));
        }

        $this->dashboard('jobseeker/pages/application-assistant', [
            'title' => 'Application Assistant',
            'activeNav' => 'overview',
            'job' => $data['job'],
            'resumes' => $data['resumes'],
            'selectedResumeId' => $data['selected_resume_id'],
            'analysis' => $data['analysis'],
            'versions' => $data['versions'],
            'history' => $data['history'],
            'deletedHistory' => $data['deleted_history'],
            'version' => $data['version'],
            'disclaimer' => $data['disclaimer'],
        ]);
    }

    public function analyze(string $job): void
    {
        $resumeId = (int) ($_POST['resume_id'] ?? 0);

        try {
            $result = $this->assistant->analyze($this->actor(), (int) $job, $resumeId);
        } catch (ApplicationAssistantException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/jobs/' . (int) $job . '/application-assistant'));
        }

        $this->flashRedirect(
            '/jobseeker/jobs/' . (int) $job . '/application-assistant?resume=' . $resumeId,
            'success',
            $result['message']
        );
    }

    public function recalculate(string $job): void
    {
        $resumeId = (int) ($_POST['resume_id'] ?? 0);

        try {
            $result = $this->assistant->recalculate($this->actor(), (int) $job, $resumeId);
        } catch (ApplicationAssistantException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/jobs/' . (int) $job . '/application-assistant'));
        }

        $this->flashRedirect(
            '/jobseeker/jobs/' . (int) $job . '/application-assistant?resume=' . $resumeId,
            'success',
            $result['message']
        );
    }

    public function history(string $job): void
    {
        try {
            $data = $this->assistant->historyPage($this->actor(), (int) $job);
        } catch (ApplicationAssistantException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker'));
        }

        $this->dashboard('jobseeker/pages/application-assistant-history', [
            'title' => 'Application Assistant History',
            'activeNav' => 'overview',
            'job' => $data['job'],
            'history' => $data['history'],
            'deletedHistory' => $data['deleted_history'],
            'versions' => $data['versions'],
            'version' => $data['version'],
        ]);
    }

    public function deleteHistory(string $job, string $history): void
    {
        try {
            $result = $this->assistant->softDeleteHistory($this->actor(), (int) $job, (int) $history);
        } catch (ApplicationAssistantException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/jobs/' . (int) $job . '/application-assistant/history'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/jobs/' . (int) $job . '/application-assistant/history', $type, $result['message']);
    }

    public function restoreHistory(string $job, string $history): void
    {
        try {
            $result = $this->assistant->restoreHistory($this->actor(), (int) $job, (int) $history);
        } catch (ApplicationAssistantException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/jobs/' . (int) $job . '/application-assistant/history'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/jobs/' . (int) $job . '/application-assistant/history', $type, $result['message']);
    }

    public function purgeHistory(string $job, string $history): void
    {
        try {
            $result = $this->assistant->purgeHistory($this->actor(), (int) $job, (int) $history);
        } catch (ApplicationAssistantException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/jobs/' . (int) $job . '/application-assistant/history'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/jobs/' . (int) $job . '/application-assistant/history', $type, $result['message']);
    }

    public function clearHistory(string $job): void
    {
        try {
            $result = $this->assistant->clearHistory($this->actor(), (int) $job);
        } catch (ApplicationAssistantException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/jobs/' . (int) $job . '/application-assistant/history'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/jobs/' . (int) $job . '/application-assistant/history', $type, $result['message']);
    }

    public function exportPdf(string $job, string $analysisId): void
    {
        try {
            $file = $this->assistant->exportPdf($this->actor(), (int) $job, (int) $analysisId);
        } catch (ApplicationAssistantException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/jobs/' . (int) $job . '/application-assistant'));
        }

        header('Content-Type: ' . $file['mime']);
        header('Content-Disposition: attachment; filename="' . $file['filename'] . '"');
        header('Content-Length: ' . (string) strlen($file['content']));
        header('Cache-Control: no-store');
        echo $file['content'];
        exit;
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers\JobSeeker;

use JobVisa\App\Domain\SalaryIntelligence\Exceptions\SalaryIntelligenceException;
use JobVisa\App\Domain\SalaryIntelligence\Services\SalaryIntelligenceService;
use JobVisa\App\Security\SessionManager;

/**
 * AI Salary Intelligence (Sprint 3.3).
 */
final class SalaryIntelligenceController extends JobSeekerController
{
    private SalaryIntelligenceService $salary;

    public function __construct()
    {
        parent::__construct();
        $this->salary = container(SalaryIntelligenceService::class);
    }

    public function show(string $id): void
    {
        try {
            $data = $this->salary->page($this->actor(), (int) $id);
        } catch (SalaryIntelligenceException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $this->dashboard('jobseeker/pages/resumes/salary-intelligence', [
            'title' => 'Salary Intelligence',
            'activeNav' => 'resumes',
            'resume' => $data['resume'],
            'completion' => $data['completion'],
            'prediction' => $data['prediction'],
            'versions' => $data['versions'],
            'history' => $data['history'],
            'deletedHistory' => $data['deleted_history'],
            'canEdit' => $data['can_edit'],
            'version' => $data['version'],
            'disclaimer' => $data['disclaimer'],
            'resumeSection' => 'salary-intelligence',
        ]);
    }

    public function calculate(string $id): void
    {
        try {
            $result = $this->salary->calculate($this->actor(), (int) $id);
        } catch (SalaryIntelligenceException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/salary-intelligence'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/salary-intelligence', $type, $result['message']);
    }

    public function recalculate(string $id): void
    {
        try {
            $result = $this->salary->recalculate($this->actor(), (int) $id);
        } catch (SalaryIntelligenceException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/salary-intelligence'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/salary-intelligence', $type, $result['message']);
    }

    public function history(string $id): void
    {
        try {
            $data = $this->salary->historyPage($this->actor(), (int) $id);
        } catch (SalaryIntelligenceException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $this->dashboard('jobseeker/pages/resumes/salary-intelligence-history', [
            'title' => 'Salary History',
            'activeNav' => 'resumes',
            'resume' => $data['resume'],
            'history' => $data['history'],
            'deletedHistory' => $data['deleted_history'],
            'versions' => $data['versions'],
            'version' => $data['version'],
            'resumeSection' => 'salary-intelligence',
        ]);
    }

    public function deleteHistory(string $id, string $historyId): void
    {
        try {
            $result = $this->salary->softDeleteHistory($this->actor(), (int) $id, (int) $historyId);
        } catch (SalaryIntelligenceException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/salary-intelligence/history'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/salary-intelligence/history', $type, $result['message']);
    }

    public function restoreHistory(string $id, string $historyId): void
    {
        try {
            $result = $this->salary->restoreHistory($this->actor(), (int) $id, (int) $historyId);
        } catch (SalaryIntelligenceException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/salary-intelligence/history'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/salary-intelligence/history', $type, $result['message']);
    }

    public function purgeHistory(string $id, string $historyId): void
    {
        try {
            $result = $this->salary->purgeHistory($this->actor(), (int) $id, (int) $historyId);
        } catch (SalaryIntelligenceException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/salary-intelligence/history'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/salary-intelligence/history', $type, $result['message']);
    }

    public function clearHistory(string $id): void
    {
        try {
            $result = $this->salary->clearHistory($this->actor(), (int) $id);
        } catch (SalaryIntelligenceException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/salary-intelligence/history'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/salary-intelligence/history', $type, $result['message']);
    }

    public function exportPdf(string $id, string $predictionId): void
    {
        try {
            $file = $this->salary->exportPdf($this->actor(), (int) $id, (int) $predictionId);
        } catch (SalaryIntelligenceException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/salary-intelligence'));
        }

        header('Content-Type: ' . $file['mime']);
        header('Content-Disposition: attachment; filename="' . $file['filename'] . '"');
        header('Content-Length: ' . (string) strlen($file['content']));
        header('Cache-Control: no-store');
        echo $file['content'];
        exit;
    }
}

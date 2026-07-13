<?php

declare(strict_types=1);

namespace App\Controllers\JobSeeker;

use JobVisa\App\Domain\CareerCoach\Exceptions\CareerCoachException;
use JobVisa\App\Domain\CareerCoach\Services\CareerCoachService;
use JobVisa\App\Security\SessionManager;

/**
 * AI Career Coach (Sprint 2F.8).
 */
final class CareerCoachController extends JobSeekerController
{
    private CareerCoachService $coach;

    public function __construct()
    {
        parent::__construct();
        $this->coach = container(CareerCoachService::class);
    }

    public function show(string $id): void
    {
        try {
            $data = $this->coach->page($this->actor(), (int) $id, false);
        } catch (CareerCoachException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $this->dashboard('jobseeker/pages/resumes/career-coach', [
            'title' => 'AI Career Coach',
            'activeNav' => 'resumes',
            'resume' => $data['resume'],
            'completion' => $data['completion'],
            'coach' => $data['coach'],
            'history' => $data['history'],
            'deletedHistory' => $data['deleted_history'],
            'canEdit' => $data['can_edit'],
            'version' => $data['version'],
            'disclaimer' => $data['disclaimer'],
            'resumeSection' => 'career-coach',
        ]);
    }

    public function recalculate(string $id): void
    {
        $targetRole = isset($_POST['target_role']) ? trim((string) $_POST['target_role']) : null;
        if ($targetRole === '') {
            $targetRole = null;
        }

        try {
            $result = $this->coach->recalculate($this->actor(), (int) $id, $targetRole);
        } catch (CareerCoachException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/career-coach', $type, $result['message']);
    }

    public function deleteHistory(string $id, string $historyId): void
    {
        try {
            $result = $this->coach->softDeleteHistoryEntry($this->actor(), (int) $id, (int) $historyId);
        } catch (CareerCoachException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/career-coach', $type, $result['message']);
    }

    public function restoreHistory(string $id, string $historyId): void
    {
        try {
            $result = $this->coach->restoreHistoryEntry($this->actor(), (int) $id, (int) $historyId);
        } catch (CareerCoachException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/career-coach', $type, $result['message']);
    }

    public function clearHistory(string $id): void
    {
        try {
            $result = $this->coach->clearHistory($this->actor(), (int) $id);
        } catch (CareerCoachException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/career-coach', $type, $result['message']);
    }
}

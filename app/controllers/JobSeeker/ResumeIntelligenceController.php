<?php

declare(strict_types=1);

namespace App\Controllers\JobSeeker;

use JobVisa\App\Domain\Resume\Exceptions\ResumeException;
use JobVisa\App\Domain\Resume\Intelligence\Services\ResumeIntelligenceService;
use JobVisa\App\Security\SessionManager;

/**
 * Resume Intelligence dashboard (Sprint 2F.1 / 2F.2).
 */
final class ResumeIntelligenceController extends JobSeekerController
{
    private ResumeIntelligenceService $intelligence;

    public function __construct()
    {
        parent::__construct();
        $this->intelligence = container(ResumeIntelligenceService::class);
    }

    public function show(string $id): void
    {
        try {
            $data = $this->intelligence->page($this->actor(), (int) $id, false);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $this->dashboard('jobseeker/pages/resumes/intelligence', [
            'title' => 'Resume Intelligence',
            'activeNav' => 'resumes',
            'resume' => $data['resume'],
            'completion' => $data['completion'],
            'intelligence' => $data['intelligence'],
            'history' => $data['history'] ?? [],
            'canEdit' => $data['can_edit'],
            'disclaimer' => $data['disclaimer'],
            'resumeSection' => 'intelligence',
        ]);
    }

    public function recalculate(string $id): void
    {
        $targetRole = isset($_POST['target_role']) ? trim((string) $_POST['target_role']) : null;
        if ($targetRole === '') {
            $targetRole = null;
        }

        try {
            $result = $this->intelligence->recalculate($this->actor(), (int) $id, $targetRole);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/intelligence', $type, $result['message']);
    }

    public function deleteHistory(string $id, string $historyId): void
    {
        try {
            $result = $this->intelligence->softDeleteHistoryEntry($this->actor(), (int) $id, (int) $historyId);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/intelligence', $type, $result['message']);
    }

    public function clearHistory(string $id): void
    {
        try {
            $result = $this->intelligence->clearHistory($this->actor(), (int) $id);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/intelligence', $type, $result['message']);
    }
}

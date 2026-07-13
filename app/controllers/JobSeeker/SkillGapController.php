<?php

declare(strict_types=1);

namespace App\Controllers\JobSeeker;

use JobVisa\App\Domain\SkillGap\Exceptions\SkillGapException;
use JobVisa\App\Domain\SkillGap\Services\SkillGapService;
use JobVisa\App\Security\SessionManager;

/**
 * AI Skill Gap Analyzer (Sprint 3.4).
 */
final class SkillGapController extends JobSeekerController
{
    private SkillGapService $skillGap;

    public function __construct()
    {
        parent::__construct();
        $this->skillGap = container(SkillGapService::class);
    }

    public function show(string $id): void
    {
        $jobId = isset($_GET['job']) ? (int) $_GET['job'] : null;
        if ($jobId !== null && $jobId < 1) {
            $jobId = null;
        }

        try {
            $data = $this->skillGap->page($this->actor(), (int) $id, $jobId);
        } catch (SkillGapException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $this->dashboard('jobseeker/pages/resumes/skill-gap', [
            'title' => 'Skill Gap Analyzer',
            'activeNav' => 'resumes',
            'resume' => $data['resume'],
            'completion' => $data['completion'],
            'matchedJobs' => $data['matched_jobs'],
            'selectedJobId' => $data['selected_job_id'],
            'analysis' => $data['analysis'],
            'versions' => $data['versions'],
            'history' => $data['history'],
            'deletedHistory' => $data['deleted_history'],
            'canEdit' => $data['can_edit'],
            'version' => $data['version'],
            'disclaimer' => $data['disclaimer'],
            'resumeSection' => 'skill-gap',
        ]);
    }

    public function analyze(string $id): void
    {
        $jobId = (int) ($_POST['job_id'] ?? 0);

        try {
            $result = $this->skillGap->analyze($this->actor(), (int) $id, $jobId);
        } catch (SkillGapException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/skill-gap'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/skill-gap?job=' . $jobId,
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }

    public function recalculate(string $id): void
    {
        $jobId = (int) ($_POST['job_id'] ?? 0);

        try {
            $result = $this->skillGap->recalculate($this->actor(), (int) $id, $jobId);
        } catch (SkillGapException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/skill-gap'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/skill-gap?job=' . $jobId,
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }

    public function history(string $id): void
    {
        try {
            $data = $this->skillGap->historyPage($this->actor(), (int) $id);
        } catch (SkillGapException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $this->dashboard('jobseeker/pages/resumes/skill-gap-history', [
            'title' => 'Skill Gap History',
            'activeNav' => 'resumes',
            'resume' => $data['resume'],
            'history' => $data['history'],
            'deletedHistory' => $data['deleted_history'],
            'versions' => $data['versions'],
            'version' => $data['version'],
            'resumeSection' => 'skill-gap',
        ]);
    }

    public function deleteHistory(string $id, string $historyId): void
    {
        try {
            $result = $this->skillGap->softDeleteHistory($this->actor(), (int) $id, (int) $historyId);
        } catch (SkillGapException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/skill-gap/history'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/skill-gap/history',
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }

    public function restoreHistory(string $id, string $historyId): void
    {
        try {
            $result = $this->skillGap->restoreHistory($this->actor(), (int) $id, (int) $historyId);
        } catch (SkillGapException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/skill-gap/history'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/skill-gap/history',
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }

    public function purgeHistory(string $id, string $historyId): void
    {
        try {
            $result = $this->skillGap->purgeHistory($this->actor(), (int) $id, (int) $historyId);
        } catch (SkillGapException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/skill-gap/history'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/skill-gap/history',
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }

    public function clearHistory(string $id): void
    {
        try {
            $result = $this->skillGap->clearHistory($this->actor(), (int) $id);
        } catch (SkillGapException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/skill-gap/history'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/skill-gap/history',
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }

    public function exportPdf(string $id, string $analysisId): void
    {
        try {
            $file = $this->skillGap->exportPdf($this->actor(), (int) $id, (int) $analysisId);
        } catch (SkillGapException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/skill-gap'));
        }

        header('Content-Type: ' . $file['mime']);
        header('Content-Disposition: attachment; filename="' . $file['filename'] . '"');
        header('Content-Length: ' . (string) strlen($file['content']));
        header('Cache-Control: no-store');
        echo $file['content'];
        exit;
    }
}

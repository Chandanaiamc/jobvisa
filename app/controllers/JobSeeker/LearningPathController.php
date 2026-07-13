<?php

declare(strict_types=1);

namespace App\Controllers\JobSeeker;

use JobVisa\App\Domain\LearningPath\Exceptions\LearningPathException;
use JobVisa\App\Domain\LearningPath\Services\LearningPathService;
use JobVisa\App\Security\SessionManager;

/**
 * AI Learning Path Generator (Sprint 3.5).
 */
final class LearningPathController extends JobSeekerController
{
    private LearningPathService $learningPaths;

    public function __construct()
    {
        parent::__construct();
        $this->learningPaths = container(LearningPathService::class);
    }

    public function show(string $id): void
    {
        try {
            $data = $this->learningPaths->page($this->actor(), (int) $id);
        } catch (LearningPathException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $this->dashboard('jobseeker/pages/resumes/learning-path', [
            'title' => 'Learning Path',
            'activeNav' => 'resumes',
            'resume' => $data['resume'],
            'completion' => $data['completion'],
            'learningPath' => $data['path'],
            'defaultCareerGoal' => $data['default_career_goal'],
            'versions' => $data['versions'],
            'history' => $data['history'],
            'deletedHistory' => $data['deleted_history'],
            'canEdit' => $data['can_edit'],
            'version' => $data['version'],
            'disclaimer' => $data['disclaimer'],
            'resumeSection' => 'learning-path',
        ]);
    }

    public function generate(string $id): void
    {
        $goal = isset($_POST['career_goal']) ? (string) $_POST['career_goal'] : null;

        try {
            $result = $this->learningPaths->generate($this->actor(), (int) $id, $goal);
        } catch (LearningPathException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/learning-path'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/learning-path',
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }

    public function recalculate(string $id): void
    {
        $goal = isset($_POST['career_goal']) ? (string) $_POST['career_goal'] : null;

        try {
            $result = $this->learningPaths->recalculate($this->actor(), (int) $id, $goal);
        } catch (LearningPathException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/learning-path'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/learning-path',
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }

    public function completeMilestone(string $id, string $pathId): void
    {
        $key = (string) ($_POST['milestone_key'] ?? '');
        $done = !isset($_POST['done']) || (string) $_POST['done'] !== '0';

        try {
            $result = $this->learningPaths->toggleMilestone($this->actor(), (int) $id, (int) $pathId, $key, $done);
        } catch (LearningPathException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/learning-path'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/learning-path',
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }

    public function history(string $id): void
    {
        try {
            $data = $this->learningPaths->historyPage($this->actor(), (int) $id);
        } catch (LearningPathException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $this->dashboard('jobseeker/pages/resumes/learning-path-history', [
            'title' => 'Learning Path History',
            'activeNav' => 'resumes',
            'resume' => $data['resume'],
            'history' => $data['history'],
            'deletedHistory' => $data['deleted_history'],
            'versions' => $data['versions'],
            'version' => $data['version'],
            'resumeSection' => 'learning-path',
        ]);
    }

    public function deleteHistory(string $id, string $historyId): void
    {
        try {
            $result = $this->learningPaths->softDeleteHistory($this->actor(), (int) $id, (int) $historyId);
        } catch (LearningPathException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/learning-path/history'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/learning-path/history',
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }

    public function restoreHistory(string $id, string $historyId): void
    {
        try {
            $result = $this->learningPaths->restoreHistory($this->actor(), (int) $id, (int) $historyId);
        } catch (LearningPathException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/learning-path/history'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/learning-path/history',
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }

    public function purgeHistory(string $id, string $historyId): void
    {
        try {
            $result = $this->learningPaths->purgeHistory($this->actor(), (int) $id, (int) $historyId);
        } catch (LearningPathException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/learning-path/history'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/learning-path/history',
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }

    public function clearHistory(string $id): void
    {
        try {
            $result = $this->learningPaths->clearHistory($this->actor(), (int) $id);
        } catch (LearningPathException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/learning-path/history'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/learning-path/history',
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }

    public function exportPdf(string $id, string $pathId): void
    {
        try {
            $file = $this->learningPaths->exportPdf($this->actor(), (int) $id, (int) $pathId);
        } catch (LearningPathException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/learning-path'));
        }

        header('Content-Type: ' . $file['mime']);
        header('Content-Disposition: attachment; filename="' . $file['filename'] . '"');
        header('Content-Length: ' . (string) strlen($file['content']));
        header('Cache-Control: no-store');
        echo $file['content'];
        exit;
    }
}

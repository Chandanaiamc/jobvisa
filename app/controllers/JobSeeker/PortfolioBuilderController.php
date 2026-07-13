<?php

declare(strict_types=1);

namespace App\Controllers\JobSeeker;

use JobVisa\App\Domain\PortfolioBuilder\Exceptions\PortfolioBuilderException;
use JobVisa\App\Domain\PortfolioBuilder\Services\PortfolioBuilderService;
use JobVisa\App\Security\SessionManager;

/**
 * AI Portfolio & Project Builder (Sprint 3.6).
 */
final class PortfolioBuilderController extends JobSeekerController
{
    private PortfolioBuilderService $builder;

    public function __construct()
    {
        parent::__construct();
        $this->builder = container(PortfolioBuilderService::class);
    }

    public function show(string $id): void
    {
        try {
            $data = $this->builder->page($this->actor(), (int) $id);
        } catch (PortfolioBuilderException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $this->dashboard('jobseeker/pages/resumes/portfolio-builder', [
            'title' => 'Portfolio Builder',
            'activeNav' => 'resumes',
            'resume' => $data['resume'],
            'completion' => $data['completion'],
            'portfolioPlan' => $data['plan'],
            'defaultCareerGoal' => $data['default_career_goal'],
            'versions' => $data['versions'],
            'history' => $data['history'],
            'deletedHistory' => $data['deleted_history'],
            'canEdit' => $data['can_edit'],
            'version' => $data['version'],
            'disclaimer' => $data['disclaimer'],
            'resumeSection' => 'portfolio-builder',
        ]);
    }

    public function generate(string $id): void
    {
        $goal = isset($_POST['career_goal']) ? (string) $_POST['career_goal'] : null;

        try {
            $result = $this->builder->generate($this->actor(), (int) $id, $goal);
        } catch (PortfolioBuilderException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/portfolio-builder'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/portfolio-builder',
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }

    public function recalculate(string $id): void
    {
        $goal = isset($_POST['career_goal']) ? (string) $_POST['career_goal'] : null;

        try {
            $result = $this->builder->recalculate($this->actor(), (int) $id, $goal);
        } catch (PortfolioBuilderException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/portfolio-builder'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/portfolio-builder',
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }

    public function history(string $id): void
    {
        try {
            $data = $this->builder->historyPage($this->actor(), (int) $id);
        } catch (PortfolioBuilderException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $this->dashboard('jobseeker/pages/resumes/portfolio-builder-history', [
            'title' => 'Portfolio Builder History',
            'activeNav' => 'resumes',
            'resume' => $data['resume'],
            'history' => $data['history'],
            'deletedHistory' => $data['deleted_history'],
            'versions' => $data['versions'],
            'version' => $data['version'],
            'resumeSection' => 'portfolio-builder',
        ]);
    }

    public function deleteHistory(string $id, string $historyId): void
    {
        try {
            $result = $this->builder->softDeleteHistory($this->actor(), (int) $id, (int) $historyId);
        } catch (PortfolioBuilderException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/portfolio-builder/history'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/portfolio-builder/history',
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }

    public function restoreHistory(string $id, string $historyId): void
    {
        try {
            $result = $this->builder->restoreHistory($this->actor(), (int) $id, (int) $historyId);
        } catch (PortfolioBuilderException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/portfolio-builder/history'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/portfolio-builder/history',
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }

    public function purgeHistory(string $id, string $historyId): void
    {
        try {
            $result = $this->builder->purgeHistory($this->actor(), (int) $id, (int) $historyId);
        } catch (PortfolioBuilderException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/portfolio-builder/history'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/portfolio-builder/history',
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }

    public function clearHistory(string $id): void
    {
        try {
            $result = $this->builder->clearHistory($this->actor(), (int) $id);
        } catch (PortfolioBuilderException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/portfolio-builder/history'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/portfolio-builder/history',
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }

    public function exportPdf(string $id, string $planId): void
    {
        try {
            $file = $this->builder->exportPdf($this->actor(), (int) $id, (int) $planId);
        } catch (PortfolioBuilderException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/portfolio-builder'));
        }

        header('Content-Type: ' . $file['mime']);
        header('Content-Disposition: attachment; filename="' . $file['filename'] . '"');
        header('Content-Length: ' . (string) strlen($file['content']));
        header('Cache-Control: no-store');
        echo $file['content'];
        exit;
    }
}

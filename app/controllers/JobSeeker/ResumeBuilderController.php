<?php

declare(strict_types=1);

namespace App\Controllers\JobSeeker;

use JobVisa\App\Domain\ResumeBuilder\Exceptions\ResumeBuilderException;
use JobVisa\App\Domain\ResumeBuilder\Services\ResumeBuilderService;
use JobVisa\App\Security\SessionManager;

/**
 * AI Resume Builder (Sprint 2F.9).
 */
final class ResumeBuilderController extends JobSeekerController
{
    private ResumeBuilderService $builder;

    public function __construct()
    {
        parent::__construct();
        $this->builder = container(ResumeBuilderService::class);
    }

    public function show(string $id): void
    {
        $previewId = isset($_GET['version']) ? (int) $_GET['version'] : null;
        if ($previewId !== null && $previewId < 1) {
            $previewId = null;
        }

        try {
            $data = $this->builder->page($this->actor(), (int) $id, $previewId);
        } catch (ResumeBuilderException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $this->dashboard('jobseeker/pages/resumes/ai-builder', [
            'title' => 'AI Resume Builder',
            'activeNav' => 'resumes',
            'resume' => $data['resume'],
            'completion' => $data['completion'],
            'preview' => $data['preview'],
            'versions' => $data['versions'],
            'history' => $data['history'],
            'deletedHistory' => $data['deleted_history'],
            'canEdit' => $data['can_edit'],
            'version' => $data['version'],
            'disclaimer' => $data['disclaimer'],
            'resumeSection' => 'ai-builder',
        ]);
    }

    public function generate(string $id): void
    {
        $targetRole = isset($_POST['target_role']) ? trim((string) $_POST['target_role']) : null;
        $label = isset($_POST['version_label']) ? trim((string) $_POST['version_label']) : null;

        try {
            $result = $this->builder->generate($this->actor(), (int) $id, $targetRole, $label);
        } catch (ResumeBuilderException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/ai-builder?version=' . (int) $result['version_id'],
            'success',
            $result['message']
        );
    }

    public function regenerate(string $id): void
    {
        $targetRole = isset($_POST['target_role']) ? trim((string) $_POST['target_role']) : null;

        try {
            $result = $this->builder->regenerate($this->actor(), (int) $id, $targetRole);
        } catch (ResumeBuilderException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/ai-builder?version=' . (int) $result['version_id'],
            'success',
            $result['message']
        );
    }

    public function saveVersion(string $id, string $versionId): void
    {
        try {
            $result = $this->builder->saveVersion($this->actor(), (int) $id, (int) $versionId);
        } catch (ResumeBuilderException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/ai-builder'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/ai-builder?version=' . (int) $versionId,
            $type,
            $result['message']
        );
    }

    public function activateVersion(string $id, string $versionId): void
    {
        try {
            $result = $this->builder->activateVersion($this->actor(), (int) $id, (int) $versionId);
        } catch (ResumeBuilderException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/ai-builder'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/ai-builder?version=' . (int) $versionId,
            $type,
            $result['message']
        );
    }

    public function deleteVersion(string $id, string $versionId): void
    {
        try {
            $result = $this->builder->softDeleteVersion($this->actor(), (int) $id, (int) $versionId);
        } catch (ResumeBuilderException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/ai-builder'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/ai-builder', $type, $result['message']);
    }

    public function deleteHistory(string $id, string $historyId): void
    {
        try {
            $result = $this->builder->softDeleteHistoryEntry($this->actor(), (int) $id, (int) $historyId);
        } catch (ResumeBuilderException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/ai-builder', $type, $result['message']);
    }

    public function restoreHistory(string $id, string $historyId): void
    {
        try {
            $result = $this->builder->restoreHistoryEntry($this->actor(), (int) $id, (int) $historyId);
        } catch (ResumeBuilderException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/ai-builder', $type, $result['message']);
    }

    public function clearHistory(string $id): void
    {
        try {
            $result = $this->builder->clearHistory($this->actor(), (int) $id);
        } catch (ResumeBuilderException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/ai-builder', $type, $result['message']);
    }
}

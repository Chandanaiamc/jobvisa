<?php

declare(strict_types=1);

namespace App\Controllers\JobSeeker;

use JobVisa\App\Domain\CoverLetter\Exceptions\CoverLetterException;
use JobVisa\App\Domain\CoverLetter\Services\CoverLetterService;
use JobVisa\App\Security\SessionManager;

/**
 * AI Cover Letter Generator (Sprint 3.1).
 */
final class CoverLetterController extends JobSeekerController
{
    private CoverLetterService $letters;

    public function __construct()
    {
        parent::__construct();
        $this->letters = container(CoverLetterService::class);
    }

    public function show(string $id): void
    {
        $previewId = isset($_GET['version']) ? (int) $_GET['version'] : null;
        if ($previewId !== null && $previewId < 1) {
            $previewId = null;
        }

        try {
            $data = $this->letters->page($this->actor(), (int) $id, $previewId);
        } catch (CoverLetterException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $this->dashboard('jobseeker/pages/resumes/cover-letters', [
            'title' => 'AI Cover Letter',
            'activeNav' => 'resumes',
            'resume' => $data['resume'],
            'completion' => $data['completion'],
            'preview' => $data['preview'],
            'versions' => $data['versions'],
            'matchedJobs' => $data['matched_jobs'],
            'styles' => $data['styles'],
            'history' => $data['history'],
            'deletedHistory' => $data['deleted_history'],
            'canEdit' => $data['can_edit'],
            'version' => $data['version'],
            'disclaimer' => $data['disclaimer'],
            'resumeSection' => 'cover-letters',
        ]);
    }

    public function generate(string $id): void
    {
        try {
            $result = $this->letters->generate(
                $this->actor(),
                (int) $id,
                isset($_POST['job_id']) ? (int) $_POST['job_id'] : null,
                isset($_POST['style']) ? (string) $_POST['style'] : null,
                isset($_POST['tone']) ? (string) $_POST['tone'] : null,
                isset($_POST['version_label']) ? (string) $_POST['version_label'] : null,
            );
        } catch (CoverLetterException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/cover-letters'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/cover-letters?version=' . (int) $result['version_id'],
            'success',
            $result['message']
        );
    }

    public function regenerate(string $id): void
    {
        try {
            $result = $this->letters->regenerate(
                $this->actor(),
                (int) $id,
                isset($_POST['job_id']) ? (int) $_POST['job_id'] : null,
                isset($_POST['style']) ? (string) $_POST['style'] : null,
                isset($_POST['tone']) ? (string) $_POST['tone'] : null,
            );
        } catch (CoverLetterException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/cover-letters'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/cover-letters?version=' . (int) $result['version_id'],
            'success',
            $result['message']
        );
    }

    public function saveVersion(string $id, string $versionId): void
    {
        try {
            $result = $this->letters->saveVersion($this->actor(), (int) $id, (int) $versionId);
        } catch (CoverLetterException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/cover-letters'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/cover-letters?version=' . (int) $versionId,
            $type,
            $result['message']
        );
    }

    public function deleteVersion(string $id, string $versionId): void
    {
        try {
            $result = $this->letters->softDeleteVersion($this->actor(), (int) $id, (int) $versionId);
        } catch (CoverLetterException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/cover-letters'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/cover-letters', $type, $result['message']);
    }

    public function exportPdf(string $id, string $versionId): void
    {
        try {
            $file = $this->letters->exportPdf($this->actor(), (int) $id, (int) $versionId);
        } catch (CoverLetterException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/cover-letters'));
        }

        $this->download($file);
    }

    public function exportDocx(string $id, string $versionId): void
    {
        try {
            $file = $this->letters->exportDocx($this->actor(), (int) $id, (int) $versionId);
        } catch (CoverLetterException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/cover-letters'));
        }

        $this->download($file);
    }

    public function deleteHistory(string $id, string $historyId): void
    {
        try {
            $result = $this->letters->softDeleteHistoryEntry($this->actor(), (int) $id, (int) $historyId);
        } catch (CoverLetterException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/cover-letters'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/cover-letters', $type, $result['message']);
    }

    public function restoreHistory(string $id, string $historyId): void
    {
        try {
            $result = $this->letters->restoreHistoryEntry($this->actor(), (int) $id, (int) $historyId);
        } catch (CoverLetterException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/cover-letters'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/cover-letters', $type, $result['message']);
    }

    public function purgeHistory(string $id, string $historyId): void
    {
        try {
            $result = $this->letters->permanentDeleteHistoryEntry($this->actor(), (int) $id, (int) $historyId);
        } catch (CoverLetterException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/cover-letters'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/cover-letters', $type, $result['message']);
    }

    public function clearHistory(string $id): void
    {
        try {
            $result = $this->letters->clearHistory($this->actor(), (int) $id);
        } catch (CoverLetterException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/cover-letters'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/cover-letters', $type, $result['message']);
    }

    /**
     * @param  array{filename: string, mime: string, content: string}  $file
     */
    private function download(array $file): never
    {
        header('Content-Type: ' . $file['mime']);
        header('Content-Disposition: attachment; filename="' . $file['filename'] . '"');
        header('Content-Length: ' . (string) strlen($file['content']));
        header('Cache-Control: no-store');
        echo $file['content'];
        exit;
    }
}

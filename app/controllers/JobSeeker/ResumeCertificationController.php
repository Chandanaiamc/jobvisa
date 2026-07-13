<?php

declare(strict_types=1);

namespace App\Controllers\JobSeeker;

use JobVisa\App\Domain\Resume\Exceptions\ResumeException;
use JobVisa\App\Domain\Resume\Services\ResumeCertificationService;
use JobVisa\App\Security\SessionManager;

/**
 * Resume builder — certifications & licences section.
 */
final class ResumeCertificationController extends JobSeekerController
{
    private ResumeCertificationService $certifications;

    public function __construct()
    {
        parent::__construct();
        $this->certifications = container(ResumeCertificationService::class);
    }

    public function index(string $id): void
    {
        try {
            $data = $this->certifications->form($this->actor(), (int) $id);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $this->renderIndex($data, [], $data['blank']->toFormArray(), null);
    }

    public function store(string $id): void
    {
        $actor = $this->actor();
        $resumeId = (int) $id;

        try {
            $result = $this->certifications->store($actor, $resumeId, $_POST);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        if (!($result['success'] ?? false)) {
            try {
                $data = $this->certifications->form($actor, $resumeId);
            } catch (ResumeException $e) {
                SessionManager::flash('error', $e->getMessage());
                redirect(app_url('/jobseeker/resumes'));
            }
            $this->renderIndex($data, $result['errors'] ?? ['form' => [$result['message'] ?? 'Unable to save.']], $_POST, null);

            return;
        }

        $this->flashRedirect('/jobseeker/resumes/' . $resumeId . '/certifications', 'success', $result['message']);
    }

    public function edit(string $id, string $certification): void
    {
        try {
            $data = $this->certifications->editForm($this->actor(), (int) $id, (int) $certification);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/certifications'));
        }

        $this->renderIndex($data, [], $data['item']->toFormArray(), (int) $certification);
    }

    public function update(string $id, string $certification): void
    {
        $actor = $this->actor();
        $resumeId = (int) $id;
        $certId = (int) $certification;

        try {
            $result = $this->certifications->update($actor, $resumeId, $certId, $_POST);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        if (!($result['success'] ?? false)) {
            try {
                $data = $this->certifications->editForm($actor, $resumeId, $certId);
            } catch (ResumeException $e) {
                SessionManager::flash('error', $e->getMessage());
                redirect(app_url('/jobseeker/resumes/' . $resumeId . '/certifications'));
            }
            $this->renderIndex(
                $data,
                $result['errors'] ?? ['form' => [$result['message'] ?? 'Unable to save.']],
                array_merge($data['item']->toFormArray(), $_POST),
                $certId
            );

            return;
        }

        $this->flashRedirect('/jobseeker/resumes/' . $resumeId . '/certifications', 'success', $result['message']);
    }

    public function destroy(string $id, string $certification): void
    {
        try {
            $result = $this->certifications->delete($this->actor(), (int) $id, (int) $certification);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/certifications', $type, $result['message']);
    }

    public function restore(string $id, string $certification): void
    {
        try {
            $result = $this->certifications->restore($this->actor(), (int) $id, (int) $certification);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/certifications', $type, $result['message']);
    }

    public function reorder(string $id): void
    {
        try {
            $result = $this->certifications->reorder($this->actor(), (int) $id, $_POST);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/certifications', $type, $result['message']);
    }

    public function uploadCertificate(string $id, string $certification): void
    {
        $file = $_FILES['certificate'] ?? [];
        try {
            $result = $this->certifications->uploadCertificate(
                $this->actor(),
                (int) $id,
                (int) $certification,
                is_array($file) ? $file : []
            );
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/certifications/' . (int) $certification . '/edit',
            $type,
            $result['message']
        );
    }

    public function deleteCertificate(string $id, string $certification): void
    {
        try {
            $result = $this->certifications->deleteCertificate($this->actor(), (int) $id, (int) $certification);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/certifications/' . (int) $certification . '/edit',
            $type,
            $result['message']
        );
    }

    public function downloadCertificate(string $id, string $certification): void
    {
        try {
            $file = $this->certifications->certificateDownload($this->actor(), (int) $id, (int) $certification);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        if ($file === null) {
            SessionManager::flash('error', 'Certificate not found.');
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/certifications'));
        }

        $mime = mime_content_type($file['path']) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . rawurlencode($file['name']) . '"');
        header('Content-Length: ' . (string) filesize($file['path']));
        readfile($file['path']);
        exit;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, list<string>>  $errors
     * @param  array<string, mixed>  $old
     */
    private function renderIndex(array $data, array $errors, array $old, ?int $editingId): void
    {
        $item = $data['item'] ?? null;

        $this->dashboard('jobseeker/pages/resumes/certifications', [
            'title' => $editingId ? 'Edit certification' : 'Certifications & licences',
            'activeNav' => 'resumes',
            'resume' => $data['resume'],
            'items' => $data['items'],
            'deleted' => $data['deleted'] ?? [],
            'completion' => $data['completion'],
            'canEdit' => $data['can_edit'],
            'statuses' => $data['statuses'],
            'editingItem' => $item,
            'errors' => $errors,
            'old' => $old,
            'editingId' => $editingId,
            'resumeSection' => 'certifications',
        ]);
    }
}

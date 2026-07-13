<?php

declare(strict_types=1);

namespace App\Controllers\JobSeeker;

use JobVisa\App\Domain\Resume\Exceptions\ResumeException;
use JobVisa\App\Domain\Resume\Services\ResumeLanguageService;
use JobVisa\App\Security\SessionManager;

/**
 * Resume builder — languages section with CEFR + certificates.
 */
final class ResumeLanguageController extends JobSeekerController
{
    private ResumeLanguageService $languages;

    public function __construct()
    {
        parent::__construct();
        $this->languages = container(ResumeLanguageService::class);
    }

    public function index(string $id): void
    {
        try {
            $data = $this->languages->form($this->actor(), (int) $id);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $this->renderIndex($data, [], $data['blank']->toFormArray(), null);
    }

    public function search(string $id): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $results = $this->languages->search(
                $this->actor(),
                (int) $id,
                trim((string) ($_GET['q'] ?? ''))
            );
        } catch (ResumeException $e) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => $e->getMessage(), 'results' => []], JSON_UNESCAPED_UNICODE);

            return;
        }

        echo json_encode(['success' => true, 'results' => $results], JSON_UNESCAPED_UNICODE);
    }

    public function store(string $id): void
    {
        $actor = $this->actor();
        $resumeId = (int) $id;

        try {
            $result = $this->languages->store($actor, $resumeId, $_POST);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        if (!($result['success'] ?? false)) {
            try {
                $data = $this->languages->form($actor, $resumeId);
            } catch (ResumeException $e) {
                SessionManager::flash('error', $e->getMessage());
                redirect(app_url('/jobseeker/resumes'));
            }
            $this->renderIndex($data, $result['errors'] ?? ['form' => [$result['message'] ?? 'Unable to save.']], $_POST, null);

            return;
        }

        $this->flashRedirect('/jobseeker/resumes/' . $resumeId . '/languages', 'success', $result['message']);
    }

    public function edit(string $id, string $language): void
    {
        try {
            $data = $this->languages->editForm($this->actor(), (int) $id, (int) $language);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/languages'));
        }

        $this->renderIndex($data, [], $data['item']->toFormArray(), (int) $language);
    }

    public function update(string $id, string $language): void
    {
        $actor = $this->actor();
        $resumeId = (int) $id;
        $languageRowId = (int) $language;

        try {
            $result = $this->languages->update($actor, $resumeId, $languageRowId, $_POST);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        if (!($result['success'] ?? false)) {
            try {
                $data = $this->languages->editForm($actor, $resumeId, $languageRowId);
            } catch (ResumeException $e) {
                SessionManager::flash('error', $e->getMessage());
                redirect(app_url('/jobseeker/resumes/' . $resumeId . '/languages'));
            }
            $this->renderIndex(
                $data,
                $result['errors'] ?? ['form' => [$result['message'] ?? 'Unable to save.']],
                array_merge($data['item']->toFormArray(), $_POST),
                $languageRowId
            );

            return;
        }

        $this->flashRedirect('/jobseeker/resumes/' . $resumeId . '/languages', 'success', $result['message']);
    }

    public function destroy(string $id, string $language): void
    {
        try {
            $result = $this->languages->delete($this->actor(), (int) $id, (int) $language);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/languages', $type, $result['message']);
    }

    public function restore(string $id, string $language): void
    {
        try {
            $result = $this->languages->restore($this->actor(), (int) $id, (int) $language);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/languages', $type, $result['message']);
    }

    public function reorder(string $id): void
    {
        try {
            $result = $this->languages->reorder($this->actor(), (int) $id, $_POST);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/languages', $type, $result['message']);
    }

    public function uploadCertificate(string $id, string $language): void
    {
        $file = $_FILES['certificate'] ?? [];
        try {
            $result = $this->languages->uploadCertificate(
                $this->actor(),
                (int) $id,
                (int) $language,
                is_array($file) ? $file : []
            );
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/languages/' . (int) $language . '/edit',
            $type,
            $result['message']
        );
    }

    public function deleteCertificate(string $id, string $language): void
    {
        try {
            $result = $this->languages->deleteCertificate($this->actor(), (int) $id, (int) $language);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/languages/' . (int) $language . '/edit',
            $type,
            $result['message']
        );
    }

    public function downloadCertificate(string $id, string $language): void
    {
        try {
            $file = $this->languages->certificateDownload($this->actor(), (int) $id, (int) $language);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        if ($file === null) {
            SessionManager::flash('error', 'Certificate not found.');
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/languages'));
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

        $this->dashboard('jobseeker/pages/resumes/languages', [
            'title' => $editingId ? 'Edit language' : 'Languages',
            'activeNav' => 'resumes',
            'resume' => $data['resume'],
            'items' => $data['items'],
            'deleted' => $data['deleted'] ?? [],
            'completion' => $data['completion'],
            'canEdit' => $data['can_edit'],
            'cefr' => $data['cefr'],
            'certificateTypes' => $data['certificate_types'],
            'statuses' => $data['statuses'],
            'searchUrl' => app_url($data['search_url']),
            'editingItem' => $item,
            'errors' => $errors,
            'old' => $old,
            'editingId' => $editingId,
            'resumeSection' => 'languages',
        ]);
    }
}

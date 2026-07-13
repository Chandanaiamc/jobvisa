<?php

declare(strict_types=1);

namespace App\Controllers\JobSeeker;

use JobVisa\App\Domain\Resume\Exceptions\ResumeException;
use JobVisa\App\Domain\Resume\Services\ResumeProjectService;
use JobVisa\App\Security\SessionManager;

/**
 * Resume builder — projects & portfolio section.
 */
final class ResumeProjectController extends JobSeekerController
{
    private ResumeProjectService $projects;

    public function __construct()
    {
        parent::__construct();
        $this->projects = container(ResumeProjectService::class);
    }

    public function index(string $id): void
    {
        try {
            $data = $this->projects->form($this->actor(), (int) $id);
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
            $result = $this->projects->store($actor, $resumeId, $_POST);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        if (!($result['success'] ?? false)) {
            try {
                $data = $this->projects->form($actor, $resumeId);
            } catch (ResumeException $e) {
                SessionManager::flash('error', $e->getMessage());
                redirect(app_url('/jobseeker/resumes'));
            }
            $this->renderIndex($data, $result['errors'] ?? ['form' => [$result['message'] ?? 'Unable to save.']], $_POST, null);

            return;
        }

        $this->flashRedirect('/jobseeker/resumes/' . $resumeId . '/projects', 'success', $result['message']);
    }

    public function edit(string $id, string $project): void
    {
        try {
            $data = $this->projects->editForm($this->actor(), (int) $id, (int) $project);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/projects'));
        }

        $this->renderIndex($data, [], $data['item']->toFormArray(), (int) $project);
    }

    public function update(string $id, string $project): void
    {
        $actor = $this->actor();
        $resumeId = (int) $id;
        $projectId = (int) $project;

        try {
            $result = $this->projects->update($actor, $resumeId, $projectId, $_POST);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        if (!($result['success'] ?? false)) {
            try {
                $data = $this->projects->editForm($actor, $resumeId, $projectId);
            } catch (ResumeException $e) {
                SessionManager::flash('error', $e->getMessage());
                redirect(app_url('/jobseeker/resumes/' . $resumeId . '/projects'));
            }
            $this->renderIndex(
                $data,
                $result['errors'] ?? ['form' => [$result['message'] ?? 'Unable to save.']],
                array_merge($data['item']->toFormArray(), $_POST),
                $projectId
            );

            return;
        }

        $this->flashRedirect('/jobseeker/resumes/' . $resumeId . '/projects', 'success', $result['message']);
    }

    public function destroy(string $id, string $project): void
    {
        try {
            $result = $this->projects->delete($this->actor(), (int) $id, (int) $project);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/projects', $type, $result['message']);
    }

    public function restore(string $id, string $project): void
    {
        try {
            $result = $this->projects->restore($this->actor(), (int) $id, (int) $project);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/projects', $type, $result['message']);
    }

    public function reorder(string $id): void
    {
        try {
            $result = $this->projects->reorder($this->actor(), (int) $id, $_POST);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/projects', $type, $result['message']);
    }

    public function uploadImage(string $id, string $project): void
    {
        $file = $_FILES['image'] ?? [];
        try {
            $result = $this->projects->uploadImage(
                $this->actor(),
                (int) $id,
                (int) $project,
                is_array($file) ? $file : []
            );
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/projects/' . (int) $project . '/edit',
            $type,
            $result['message']
        );
    }

    public function deleteImage(string $id, string $project): void
    {
        try {
            $result = $this->projects->deleteImage($this->actor(), (int) $id, (int) $project);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/projects/' . (int) $project . '/edit',
            $type,
            $result['message']
        );
    }

    public function uploadDocument(string $id, string $project): void
    {
        $file = $_FILES['document'] ?? [];
        try {
            $result = $this->projects->uploadDocument(
                $this->actor(),
                (int) $id,
                (int) $project,
                is_array($file) ? $file : []
            );
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/projects/' . (int) $project . '/edit',
            $type,
            $result['message']
        );
    }

    public function deleteDocument(string $id, string $project): void
    {
        try {
            $result = $this->projects->deleteDocument($this->actor(), (int) $id, (int) $project);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/projects/' . (int) $project . '/edit',
            $type,
            $result['message']
        );
    }

    public function downloadImage(string $id, string $project): void
    {
        $this->streamAsset($id, $project, 'image');
    }

    public function downloadDocument(string $id, string $project): void
    {
        $this->streamAsset($id, $project, 'document');
    }

    private function streamAsset(string $id, string $project, string $kind): void
    {
        try {
            $file = $this->projects->downloadAsset($this->actor(), (int) $id, (int) $project, $kind);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        if ($file === null) {
            SessionManager::flash('error', ucfirst($kind) . ' not found.');
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/projects'));
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

        $this->dashboard('jobseeker/pages/resumes/projects', [
            'title' => $editingId ? 'Edit project' : 'Projects & portfolio',
            'activeNav' => 'resumes',
            'resume' => $data['resume'],
            'items' => $data['items'],
            'deleted' => $data['deleted'] ?? [],
            'completion' => $data['completion'],
            'canEdit' => $data['can_edit'],
            'statuses' => $data['statuses'],
            'visibilities' => $data['visibilities'],
            'projectTypes' => $data['project_types'],
            'reorderUrl' => app_url($data['reorder_url']),
            'editingItem' => $item,
            'errors' => $errors,
            'old' => $old,
            'editingId' => $editingId,
            'resumeSection' => 'projects',
        ]);
    }
}

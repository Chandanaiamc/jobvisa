<?php

declare(strict_types=1);

namespace App\Controllers\JobSeeker;

use JobVisa\App\Domain\Resume\Exceptions\ResumeException;
use JobVisa\App\Domain\Resume\Services\ResumeEducationService;
use JobVisa\App\Security\SessionManager;

/**
 * Resume builder — multi-record education section.
 */
final class ResumeEducationController extends JobSeekerController
{
    private ResumeEducationService $education;

    public function __construct()
    {
        parent::__construct();
        $this->education = container(ResumeEducationService::class);
    }

    public function index(string $id): void
    {
        try {
            $data = $this->education->form($this->actor(), (int) $id);
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
            $result = $this->education->store($actor, $resumeId, $_POST);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        if (!($result['success'] ?? false)) {
            try {
                $data = $this->education->form($actor, $resumeId);
            } catch (ResumeException $e) {
                SessionManager::flash('error', $e->getMessage());
                redirect(app_url('/jobseeker/resumes'));
            }
            $this->renderIndex($data, $result['errors'] ?? ['form' => [$result['message'] ?? 'Unable to save.']], $_POST, null);

            return;
        }

        $this->flashRedirect('/jobseeker/resumes/' . $resumeId . '/education', 'success', $result['message']);
    }

    public function edit(string $id, string $education): void
    {
        try {
            $data = $this->education->editForm($this->actor(), (int) $id, (int) $education);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/education'));
        }

        $this->renderIndex($data, [], $data['item']->toFormArray(), (int) $education);
    }

    public function update(string $id, string $education): void
    {
        $actor = $this->actor();
        $resumeId = (int) $id;
        $educationId = (int) $education;

        try {
            $result = $this->education->update($actor, $resumeId, $educationId, $_POST);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        if (!($result['success'] ?? false)) {
            try {
                $data = $this->education->editForm($actor, $resumeId, $educationId);
            } catch (ResumeException $e) {
                SessionManager::flash('error', $e->getMessage());
                redirect(app_url('/jobseeker/resumes/' . $resumeId . '/education'));
            }
            $this->renderIndex(
                $data,
                $result['errors'] ?? ['form' => [$result['message'] ?? 'Unable to save.']],
                array_merge($data['item']->toFormArray(), $_POST),
                $educationId
            );

            return;
        }

        $this->flashRedirect('/jobseeker/resumes/' . $resumeId . '/education', 'success', $result['message']);
    }

    public function destroy(string $id, string $education): void
    {
        try {
            $result = $this->education->delete($this->actor(), (int) $id, (int) $education);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/education', $type, $result['message']);
    }

    public function restore(string $id, string $education): void
    {
        try {
            $result = $this->education->restore($this->actor(), (int) $id, (int) $education);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/education', $type, $result['message']);
    }

    public function reorder(string $id): void
    {
        try {
            $result = $this->education->reorder($this->actor(), (int) $id, $_POST);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/education', $type, $result['message']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, list<string>>  $errors
     * @param  array<string, mixed>  $old
     */
    private function renderIndex(array $data, array $errors, array $old, ?int $editingId): void
    {
        $this->dashboard('jobseeker/pages/resumes/education', [
            'title' => $editingId ? 'Edit education' : 'Education',
            'activeNav' => 'resumes',
            'resume' => $data['resume'],
            'items' => $data['items'],
            'deleted' => $data['deleted'] ?? [],
            'countries' => $data['countries'],
            'completion' => $data['completion'],
            'canEdit' => $data['can_edit'],
            'qualificationTypes' => $data['qualification_types'],
            'statuses' => $data['statuses'],
            'errors' => $errors,
            'old' => $old,
            'editingId' => $editingId,
            'resumeSection' => 'education',
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers\JobSeeker;

use JobVisa\App\Domain\Resume\Exceptions\ResumeException;
use JobVisa\App\Domain\Resume\Services\ResumeExperienceService;
use JobVisa\App\Security\SessionManager;

/**
 * Resume builder — multi-record work experience section.
 */
final class ResumeExperienceController extends JobSeekerController
{
    private ResumeExperienceService $experience;

    public function __construct()
    {
        parent::__construct();
        $this->experience = container(ResumeExperienceService::class);
    }

    public function index(string $id): void
    {
        try {
            $data = $this->experience->form($this->actor(), (int) $id);
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
            $result = $this->experience->store($actor, $resumeId, $_POST);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        if (!($result['success'] ?? false)) {
            try {
                $data = $this->experience->form($actor, $resumeId);
            } catch (ResumeException $e) {
                SessionManager::flash('error', $e->getMessage());
                redirect(app_url('/jobseeker/resumes'));
            }
            $this->renderIndex($data, $result['errors'] ?? ['form' => [$result['message'] ?? 'Unable to save.']], $_POST, null);

            return;
        }

        $this->flashRedirect('/jobseeker/resumes/' . $resumeId . '/experience', 'success', $result['message']);
    }

    public function edit(string $id, string $experience): void
    {
        try {
            $data = $this->experience->editForm($this->actor(), (int) $id, (int) $experience);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/experience'));
        }

        $this->renderIndex($data, [], $data['item']->toFormArray(), (int) $experience);
    }

    public function update(string $id, string $experience): void
    {
        $actor = $this->actor();
        $resumeId = (int) $id;
        $experienceId = (int) $experience;

        try {
            $result = $this->experience->update($actor, $resumeId, $experienceId, $_POST);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        if (!($result['success'] ?? false)) {
            try {
                $data = $this->experience->editForm($actor, $resumeId, $experienceId);
            } catch (ResumeException $e) {
                SessionManager::flash('error', $e->getMessage());
                redirect(app_url('/jobseeker/resumes/' . $resumeId . '/experience'));
            }
            $this->renderIndex(
                $data,
                $result['errors'] ?? ['form' => [$result['message'] ?? 'Unable to save.']],
                array_merge($data['item']->toFormArray(), $_POST),
                $experienceId
            );

            return;
        }

        $this->flashRedirect('/jobseeker/resumes/' . $resumeId . '/experience', 'success', $result['message']);
    }

    public function destroy(string $id, string $experience): void
    {
        try {
            $result = $this->experience->delete($this->actor(), (int) $id, (int) $experience);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/experience', $type, $result['message']);
    }

    public function restore(string $id, string $experience): void
    {
        try {
            $result = $this->experience->restore($this->actor(), (int) $id, (int) $experience);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/experience', $type, $result['message']);
    }

    public function reorder(string $id): void
    {
        try {
            $result = $this->experience->reorder($this->actor(), (int) $id, $_POST);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/experience', $type, $result['message']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, list<string>>  $errors
     * @param  array<string, mixed>  $old
     */
    private function renderIndex(array $data, array $errors, array $old, ?int $editingId): void
    {
        if (!isset($old['skill_ids']) || !is_array($old['skill_ids'])) {
            $old['skill_ids'] = [];
        }

        $this->dashboard('jobseeker/pages/resumes/experience', [
            'title' => $editingId ? 'Edit experience' : 'Work experience',
            'activeNav' => 'resumes',
            'resume' => $data['resume'],
            'items' => $data['items'],
            'deleted' => $data['deleted'] ?? [],
            'countries' => $data['countries'],
            'skillOptions' => $data['skill_options'],
            'completion' => $data['completion'],
            'canEdit' => $data['can_edit'],
            'includePrivate' => $data['include_private'],
            'employmentTypes' => $data['employment_types'],
            'statuses' => $data['statuses'],
            'errors' => $errors,
            'old' => $old,
            'editingId' => $editingId,
            'resumeSection' => 'experience',
        ]);
    }
}

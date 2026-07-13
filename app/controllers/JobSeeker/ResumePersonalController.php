<?php

declare(strict_types=1);

namespace App\Controllers\JobSeeker;

use JobVisa\App\Domain\Resume\Exceptions\ResumeException;
use JobVisa\App\Domain\Resume\Services\ResumePersonalService;
use JobVisa\App\Security\SessionManager;

/**
 * Resume builder — personal information section.
 */
final class ResumePersonalController extends JobSeekerController
{
    private ResumePersonalService $personal;

    public function __construct()
    {
        parent::__construct();
        $this->personal = container(ResumePersonalService::class);
    }

    public function edit(string $id): void
    {
        try {
            $data = $this->personal->form($this->actor(), (int) $id);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $this->dashboard('jobseeker/pages/resumes/personal', [
            'title' => 'Personal Information',
            'activeNav' => 'resumes',
            'resume' => $data['resume'],
            'personal' => $data['personal'],
            'countries' => $data['countries'],
            'cities' => $data['cities'],
            'completion' => $data['completion'],
            'canEdit' => $data['can_edit'],
            'errors' => [],
            'old' => $data['personal']->toFormArray(),
            'resumeSection' => 'personal',
        ]);
    }

    public function update(string $id): void
    {
        $actor = $this->actor();

        try {
            $result = $this->personal->save($actor, (int) $id, $_POST);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        if (!($result['success'] ?? false)) {
            try {
                $data = $this->personal->form($actor, (int) $id);
            } catch (ResumeException $e) {
                SessionManager::flash('error', $e->getMessage());
                redirect(app_url('/jobseeker/resumes'));
            }

            $old = array_merge($data['personal']->toFormArray(), $_POST);
            unset($old['email']);
            $old['email'] = $data['personal']->email;
            $old['preferred_country_ids'] = $_POST['preferred_country_ids'] ?? $data['personal']->preferredCountryIds;

            $this->dashboard('jobseeker/pages/resumes/personal', [
                'title' => 'Personal Information',
                'activeNav' => 'resumes',
                'resume' => $data['resume'],
                'personal' => $data['personal'],
                'countries' => $data['countries'],
                'cities' => $data['cities'],
                'completion' => $data['completion'],
                'canEdit' => $data['can_edit'],
                'errors' => $result['errors'] ?? ['form' => [$result['message'] ?? 'Unable to save.']],
                'old' => $old,
                'resumeSection' => 'personal',
            ]);

            return;
        }

        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/personal', 'success', $result['message']);
    }

    public function uploadPhoto(string $id): void
    {
        $file = $_FILES['photo'] ?? null;

        if (!is_array($file)) {
            SessionManager::flash('error', 'Please choose a photo to upload.');
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/personal'));
        }

        try {
            $result = $this->personal->uploadPhoto($this->actor(), (int) $id, $file);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/personal',
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }

    public function deletePhoto(string $id): void
    {
        try {
            $result = $this->personal->deletePhoto($this->actor(), (int) $id);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/personal',
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers\JobSeeker;

use JobVisa\App\JobSeeker\ProfileService;
use JobVisa\App\Security\SessionManager;

final class ProfileController extends JobSeekerController
{
    public function edit(): void
    {
        $actor = $this->actor();
        $data = container(ProfileService::class)->dashboard($actor, $this->userId());

        $this->dashboard('jobseeker/pages/profile', [
            'title' => 'Profile',
            'activeNav' => 'profile',
            'profile' => $data['profile'],
            'countries' => $data['countries'],
            'cities' => $data['cities'],
            'errors' => [],
        ]);
    }

    public function update(): void
    {
        $result = container(ProfileService::class)->update($this->actor(), $this->userId(), $_POST);

        if (!($result['success'] ?? false)) {
            $data = container(ProfileService::class)->dashboard($this->actor(), $this->userId());
            $this->dashboard('jobseeker/pages/profile', [
                'title' => 'Profile',
                'activeNav' => 'profile',
                'profile' => array_merge($data['profile'] ?? [], $_POST),
                'countries' => $data['countries'],
                'cities' => $data['cities'],
                'errors' => $result['errors'] ?? ['form' => [$result['message'] ?? 'Unable to save.']],
            ]);

            return;
        }

        $this->flashRedirect('/jobseeker/profile', 'success', $result['message']);
    }

    public function uploadAvatar(): void
    {
        $file = $_FILES['avatar'] ?? null;

        if (!is_array($file)) {
            SessionManager::flash('error', 'Please choose a photo to upload.');
            redirect(app_url('/jobseeker/profile'));
        }

        $result = container(ProfileService::class)->uploadAvatar($this->actor(), $this->userId(), $file);
        $this->flashRedirect('/jobseeker/profile', ($result['success'] ?? false) ? 'success' : 'error', $result['message']);
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers\JobSeeker;

final class SettingsController extends JobSeekerController
{
    public function index(): void
    {
        $this->dashboard('jobseeker/pages/settings', [
            'title' => 'Settings',
            'activeNav' => 'settings',
            'user' => $this->actor(),
        ]);
    }
}

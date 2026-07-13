<?php

declare(strict_types=1);

namespace App\Controllers\JobSeeker;

use JobVisa\App\JobSeeker\CvService;
use JobVisa\App\JobSeeker\EducationService;
use JobVisa\App\JobSeeker\ExperienceService;
use JobVisa\App\JobSeeker\LanguageService;
use JobVisa\App\JobSeeker\ProfileService;
use JobVisa\App\JobSeeker\SkillService;

final class DashboardController extends JobSeekerController
{
    public function index(): void
    {
        $actor = $this->actor();
        $userId = $this->userId();
        $data = container(ProfileService::class)->dashboard($actor, $userId);

        $this->dashboard('jobseeker/pages/overview', [
            'title' => 'Overview',
            'activeNav' => 'overview',
            'profile' => $data['profile'],
            'resume' => $data['resume'],
            'educationCount' => count(container(EducationService::class)->list($actor, $userId)),
            'experienceCount' => count(container(ExperienceService::class)->list($actor, $userId)),
            'skillsCount' => count(container(SkillService::class)->page($actor, $userId)['items']),
            'languagesCount' => count(container(LanguageService::class)->page($actor, $userId)['items']),
            'hasCv' => !empty($data['resume']['file_path']),
        ]);
    }
}

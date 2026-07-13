<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\View;
use JobVisa\App\Auth\AuthManager;
use JobVisa\App\JobSeeker\CvService;
use JobVisa\App\JobSeeker\EducationService;
use JobVisa\App\JobSeeker\ExperienceService;
use JobVisa\App\JobSeeker\LanguageService;
use JobVisa\App\JobSeeker\ProfileAccess;
use JobVisa\App\JobSeeker\ProfileService;
use JobVisa\App\JobSeeker\SkillService;
use JobVisa\App\Repositories\UserRepository;

/**
 * Read-only admin view of a job seeker profile.
 */
final class SeekerProfileController extends Controller
{
    public function show(string $id): void
    {
        $auth = container(AuthManager::class);
        $actor = $auth->user();
        $targetId = (int) $id;

        if ($actor === null || !container(ProfileAccess::class)->canView($actor, $targetId)) {
            http_response_code(403);
            (new View())->display('errors/403', [
                'title' => 'Forbidden',
                'message' => 'You do not have access to this profile.',
            ]);

            return;
        }

        if (!container(ProfileAccess::class)->canEdit($actor, $targetId) && ($actor['role'] ?? '') === 'seeker') {
            // seekers only see own via /jobseeker
        }

        $user = container(UserRepository::class)->findRecordById($targetId);

        if ($user === null || ($user['role'] ?? '') !== 'seeker') {
            http_response_code(404);
            (new View())->display('errors/404', ['title' => 'Not Found', 'path' => '/admin/seekers/' . $id]);

            return;
        }

        $data = container(ProfileService::class)->dashboard($actor, $targetId);

        $this->render('admin/seeker-profile', [
            'title' => 'Seeker Profile',
            'user' => $user,
            'profile' => $data['profile'],
            'completeness' => $data['completeness'],
            'education' => container(EducationService::class)->list($actor, $targetId),
            'experience' => container(ExperienceService::class)->list($actor, $targetId),
            'skills' => container(SkillService::class)->page($actor, $targetId)['items'],
            'languages' => container(LanguageService::class)->page($actor, $targetId)['items'],
            'resume' => container(CvService::class)->current($actor, $targetId),
            'canEdit' => false,
        ]);
    }
}

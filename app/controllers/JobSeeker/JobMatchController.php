<?php

declare(strict_types=1);

namespace App\Controllers\JobSeeker;

use JobVisa\App\Domain\JobMatching\Exceptions\JobMatchException;
use JobVisa\App\Domain\JobMatching\Services\JobMatchService;
use JobVisa\App\Security\SessionManager;

/**
 * Resume ↔ job matching (Sprint 2F.3).
 */
final class JobMatchController extends JobSeekerController
{
    private JobMatchService $matches;

    public function __construct()
    {
        parent::__construct();
        $this->matches = container(JobMatchService::class);
    }

    public function show(string $resume, string $job): void
    {
        try {
            $data = $this->matches->matchPage($this->actor(), (int) $resume, (int) $job, false);
        } catch (JobMatchException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $resume . '/recommended-jobs'));
        }

        $this->dashboard('jobseeker/pages/resumes/job-match', [
            'title' => 'Job Match',
            'activeNav' => 'resumes',
            'resume' => $data['resume'],
            'job' => $data['job'],
            'completion' => $data['completion'],
            'match' => $data['match'],
            'canEdit' => $data['can_edit'],
            'disclaimer' => $data['disclaimer'],
            'resumeSection' => 'recommended-jobs',
        ]);
    }

    public function recalculate(string $resume, string $job): void
    {
        try {
            $result = $this->matches->recalculate($this->actor(), (int) $resume, (int) $job);
        } catch (JobMatchException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $resume . '/jobs/' . (int) $job . '/match',
            $type,
            $result['message']
        );
    }

    public function recommended(string $resume): void
    {
        try {
            $data = $this->matches->recommendedPage($this->actor(), (int) $resume);
        } catch (JobMatchException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $this->dashboard('jobseeker/pages/resumes/recommended-jobs', [
            'title' => 'Recommended Jobs',
            'activeNav' => 'resumes',
            'resume' => $data['resume'],
            'completion' => $data['completion'],
            'recommendations' => $data['recommendations'],
            'canEdit' => $data['can_edit'],
            'disclaimer' => $data['disclaimer'],
            'resumeSection' => 'recommended-jobs',
        ]);
    }
}

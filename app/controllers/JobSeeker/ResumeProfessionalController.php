<?php

declare(strict_types=1);

namespace App\Controllers\JobSeeker;

use JobVisa\App\Domain\Resume\Exceptions\ResumeException;
use JobVisa\App\Domain\Resume\Services\ResumeProfessionalService;
use JobVisa\App\Security\Csrf;
use JobVisa\App\Security\SessionManager;

/**
 * Resume builder — professional headline & summary.
 */
final class ResumeProfessionalController extends JobSeekerController
{
    private ResumeProfessionalService $professional;

    public function __construct()
    {
        parent::__construct();
        $this->professional = container(ResumeProfessionalService::class);
    }

    public function edit(string $id): void
    {
        try {
            $data = $this->professional->form($this->actor(), (int) $id);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $this->dashboard('jobseeker/pages/resumes/professional', [
            'title' => 'Professional Summary',
            'activeNav' => 'resumes',
            'resume' => $data['resume'],
            'professional' => $data['professional'],
            'completion' => $data['completion'],
            'canEdit' => $data['can_edit'],
            'errors' => [],
            'old' => $data['professional']->toFormArray(),
            'resumeSection' => 'professional',
            'autosaveUrl' => app_url('/jobseeker/resumes/' . (int) $id . '/professional/autosave'),
        ]);
    }

    public function update(string $id): void
    {
        $actor = $this->actor();

        try {
            $result = $this->professional->save($actor, (int) $id, $_POST, false);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        if (!($result['success'] ?? false)) {
            try {
                $data = $this->professional->form($actor, (int) $id);
            } catch (ResumeException $e) {
                SessionManager::flash('error', $e->getMessage());
                redirect(app_url('/jobseeker/resumes'));
            }

            $this->dashboard('jobseeker/pages/resumes/professional', [
                'title' => 'Professional Summary',
                'activeNav' => 'resumes',
                'resume' => $data['resume'],
                'professional' => $data['professional'],
                'completion' => $data['completion'],
                'canEdit' => $data['can_edit'],
                'errors' => $result['errors'] ?? ['form' => [$result['message'] ?? 'Unable to save.']],
                'old' => array_merge($data['professional']->toFormArray(), $_POST),
                'resumeSection' => 'professional',
                'autosaveUrl' => app_url('/jobseeker/resumes/' . (int) $id . '/professional/autosave'),
            ]);

            return;
        }

        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/professional', 'success', $result['message']);
    }

    public function autosave(string $id): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $result = $this->professional->save($this->actor(), (int) $id, $_POST, true);
        } catch (ResumeException $e) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);

            return;
        }

        if ($result['success'] ?? false) {
            Csrf::rotate();
        }

        $status = ($result['success'] ?? false) ? 200 : 422;
        http_response_code($status);
        echo json_encode([
            'success' => (bool) ($result['success'] ?? false),
            'message' => $result['message'] ?? '',
            'errors' => $result['errors'] ?? new \stdClass(),
            'completion' => $result['completion']['score'] ?? null,
            'csrf_token' => csrf_token(),
        ], JSON_UNESCAPED_UNICODE);
    }
}

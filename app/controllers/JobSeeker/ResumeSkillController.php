<?php

declare(strict_types=1);

namespace App\Controllers\JobSeeker;

use JobVisa\App\Domain\Resume\Exceptions\ResumeException;
use JobVisa\App\Domain\Resume\Services\ResumeSkillService;
use JobVisa\App\Security\SessionManager;

/**
 * Resume builder — skills section with catalogue autocomplete.
 */
final class ResumeSkillController extends JobSeekerController
{
    private ResumeSkillService $skills;

    public function __construct()
    {
        parent::__construct();
        $this->skills = container(ResumeSkillService::class);
    }

    public function index(string $id): void
    {
        try {
            $data = $this->skills->form($this->actor(), (int) $id);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $this->renderIndex($data, [], $data['blank']->toFormArray(), null);
    }

    public function search(string $id): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $results = $this->skills->search(
                $this->actor(),
                (int) $id,
                trim((string) ($_GET['q'] ?? ''))
            );
        } catch (ResumeException $e) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => $e->getMessage(), 'results' => []], JSON_UNESCAPED_UNICODE);

            return;
        }

        echo json_encode(['success' => true, 'results' => $results], JSON_UNESCAPED_UNICODE);
    }

    public function store(string $id): void
    {
        $actor = $this->actor();
        $resumeId = (int) $id;

        try {
            $result = $this->skills->store($actor, $resumeId, $_POST);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        if (!($result['success'] ?? false)) {
            try {
                $data = $this->skills->form($actor, $resumeId);
            } catch (ResumeException $e) {
                SessionManager::flash('error', $e->getMessage());
                redirect(app_url('/jobseeker/resumes'));
            }
            $this->renderIndex($data, $result['errors'] ?? ['form' => [$result['message'] ?? 'Unable to save.']], $_POST, null);

            return;
        }

        $this->flashRedirect('/jobseeker/resumes/' . $resumeId . '/skills', 'success', $result['message']);
    }

    public function edit(string $id, string $skill): void
    {
        try {
            $data = $this->skills->editForm($this->actor(), (int) $id, (int) $skill);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/skills'));
        }

        $this->renderIndex($data, [], $data['item']->toFormArray(), (int) $skill);
    }

    public function update(string $id, string $skill): void
    {
        $actor = $this->actor();
        $resumeId = (int) $id;
        $skillRowId = (int) $skill;

        try {
            $result = $this->skills->update($actor, $resumeId, $skillRowId, $_POST);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        if (!($result['success'] ?? false)) {
            try {
                $data = $this->skills->editForm($actor, $resumeId, $skillRowId);
            } catch (ResumeException $e) {
                SessionManager::flash('error', $e->getMessage());
                redirect(app_url('/jobseeker/resumes/' . $resumeId . '/skills'));
            }
            $this->renderIndex(
                $data,
                $result['errors'] ?? ['form' => [$result['message'] ?? 'Unable to save.']],
                array_merge($data['item']->toFormArray(), $_POST),
                $skillRowId
            );

            return;
        }

        $this->flashRedirect('/jobseeker/resumes/' . $resumeId . '/skills', 'success', $result['message']);
    }

    public function destroy(string $id, string $skill): void
    {
        try {
            $result = $this->skills->delete($this->actor(), (int) $id, (int) $skill);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/skills', $type, $result['message']);
    }

    public function restore(string $id, string $skill): void
    {
        try {
            $result = $this->skills->restore($this->actor(), (int) $id, (int) $skill);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/skills', $type, $result['message']);
    }

    public function reorder(string $id): void
    {
        try {
            $result = $this->skills->reorder($this->actor(), (int) $id, $_POST);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/skills', $type, $result['message']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, list<string>>  $errors
     * @param  array<string, mixed>  $old
     */
    private function renderIndex(array $data, array $errors, array $old, ?int $editingId): void
    {
        $this->dashboard('jobseeker/pages/resumes/skills', [
            'title' => $editingId ? 'Edit skill' : 'Skills',
            'activeNav' => 'resumes',
            'resume' => $data['resume'],
            'items' => $data['items'],
            'deleted' => $data['deleted'] ?? [],
            'completion' => $data['completion'],
            'canEdit' => $data['can_edit'],
            'levels' => $data['levels'],
            'statuses' => $data['statuses'],
            'searchUrl' => app_url($data['search_url']),
            'errors' => $errors,
            'old' => $old,
            'editingId' => $editingId,
            'resumeSection' => 'skills',
        ]);
    }
}

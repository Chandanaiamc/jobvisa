<?php

declare(strict_types=1);

namespace App\Controllers\JobSeeker;

use JobVisa\App\Domain\Resume\Exceptions\ResumeException;
use JobVisa\App\Domain\Resume\Services\ResumeAchievementService;
use JobVisa\App\Security\SessionManager;

/**
 * Resume builder — awards & achievements section.
 */
final class ResumeAchievementController extends JobSeekerController
{
    private ResumeAchievementService $achievements;

    public function __construct()
    {
        parent::__construct();
        $this->achievements = container(ResumeAchievementService::class);
    }

    public function index(string $id): void
    {
        $query = trim((string) ($_GET['q'] ?? ''));

        try {
            $data = $this->achievements->form($this->actor(), (int) $id, $query !== '' ? $query : null);
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
            $results = $this->achievements->search(
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

    public function cities(string $id): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $countryId = (int) ($_GET['country_id'] ?? 0);

        try {
            $results = $this->achievements->citiesForCountry($this->actor(), (int) $id, $countryId);
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
            $result = $this->achievements->store($actor, $resumeId, $_POST);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        if (!($result['success'] ?? false)) {
            try {
                $data = $this->achievements->form($actor, $resumeId);
            } catch (ResumeException $e) {
                SessionManager::flash('error', $e->getMessage());
                redirect(app_url('/jobseeker/resumes'));
            }
            $this->renderIndex($data, $result['errors'] ?? ['form' => [$result['message'] ?? 'Unable to save.']], $_POST, null);

            return;
        }

        $this->flashRedirect('/jobseeker/resumes/' . $resumeId . '/achievements', 'success', $result['message']);
    }

    public function edit(string $id, string $achievement): void
    {
        try {
            $data = $this->achievements->editForm($this->actor(), (int) $id, (int) $achievement);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/achievements'));
        }

        $this->renderIndex($data, [], $data['item']->toFormArray(), (int) $achievement);
    }

    public function update(string $id, string $achievement): void
    {
        $actor = $this->actor();
        $resumeId = (int) $id;
        $achievementId = (int) $achievement;

        try {
            $result = $this->achievements->update($actor, $resumeId, $achievementId, $_POST);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        if (!($result['success'] ?? false)) {
            try {
                $data = $this->achievements->editForm($actor, $resumeId, $achievementId);
            } catch (ResumeException $e) {
                SessionManager::flash('error', $e->getMessage());
                redirect(app_url('/jobseeker/resumes/' . $resumeId . '/achievements'));
            }
            $this->renderIndex(
                $data,
                $result['errors'] ?? ['form' => [$result['message'] ?? 'Unable to save.']],
                array_merge($data['item']->toFormArray(), $_POST),
                $achievementId
            );

            return;
        }

        $this->flashRedirect('/jobseeker/resumes/' . $resumeId . '/achievements', 'success', $result['message']);
    }

    public function destroy(string $id, string $achievement): void
    {
        try {
            $result = $this->achievements->delete($this->actor(), (int) $id, (int) $achievement);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/achievements', $type, $result['message']);
    }

    public function restore(string $id, string $achievement): void
    {
        try {
            $result = $this->achievements->restore($this->actor(), (int) $id, (int) $achievement);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/achievements', $type, $result['message']);
    }

    public function reorder(string $id): void
    {
        try {
            $result = $this->achievements->reorder($this->actor(), (int) $id, $_POST);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/achievements', $type, $result['message']);
    }

    public function uploadCertificate(string $id, string $achievement): void
    {
        $file = $_FILES['certificate'] ?? [];
        try {
            $result = $this->achievements->uploadCertificate(
                $this->actor(),
                (int) $id,
                (int) $achievement,
                is_array($file) ? $file : []
            );
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/achievements/' . (int) $achievement . '/edit',
            $type,
            $result['message']
        );
    }

    public function deleteCertificate(string $id, string $achievement): void
    {
        try {
            $result = $this->achievements->deleteCertificate($this->actor(), (int) $id, (int) $achievement);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/achievements/' . (int) $achievement . '/edit',
            $type,
            $result['message']
        );
    }

    public function downloadCertificate(string $id, string $achievement): void
    {
        try {
            $file = $this->achievements->certificateDownload($this->actor(), (int) $id, (int) $achievement);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        if ($file === null) {
            SessionManager::flash('error', 'Certificate not found.');
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/achievements'));
        }

        $mime = mime_content_type($file['path']) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . rawurlencode($file['name']) . '"');
        header('Content-Length: ' . (string) filesize($file['path']));
        readfile($file['path']);
        exit;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, list<string>>  $errors
     * @param  array<string, mixed>  $old
     */
    private function renderIndex(array $data, array $errors, array $old, ?int $editingId): void
    {
        $item = $data['item'] ?? null;

        $this->dashboard('jobseeker/pages/resumes/achievements', [
            'title' => $editingId ? 'Edit achievement' : 'Awards & achievements',
            'activeNav' => 'resumes',
            'resume' => $data['resume'],
            'items' => $data['items'],
            'deleted' => $data['deleted'] ?? [],
            'completion' => $data['completion'],
            'canEdit' => $data['can_edit'],
            'statuses' => $data['statuses'],
            'visibilities' => $data['visibilities'],
            'types' => $data['types'],
            'awardLevels' => $data['award_levels'],
            'projects' => $data['projects'],
            'countries' => $data['countries'],
            'cities' => $data['cities'],
            'query' => $data['query'] ?? '',
            'searchUrl' => app_url($data['search_url']),
            'citiesUrl' => app_url($data['cities_url']),
            'editingItem' => $item,
            'errors' => $errors,
            'old' => $old,
            'editingId' => $editingId,
            'resumeSection' => 'achievements',
        ]);
    }
}

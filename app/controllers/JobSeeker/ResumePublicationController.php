<?php

declare(strict_types=1);

namespace App\Controllers\JobSeeker;

use JobVisa\App\Domain\Resume\Exceptions\ResumeException;
use JobVisa\App\Domain\Resume\Services\ResumePublicationService;
use JobVisa\App\Security\SessionManager;

/**
 * Resume builder — publications & research section.
 */
final class ResumePublicationController extends JobSeekerController
{
    private ResumePublicationService $publications;

    public function __construct()
    {
        parent::__construct();
        $this->publications = container(ResumePublicationService::class);
    }

    public function index(string $id): void
    {
        $filters = $this->filtersFromRequest();
        $page = max(1, (int) ($_GET['page'] ?? 1));

        try {
            $data = $this->publications->form($this->actor(), (int) $id, $filters, $page);
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
            $results = $this->publications->search($this->actor(), (int) $id, trim((string) ($_GET['q'] ?? '')));
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
        try {
            $results = $this->publications->citiesForCountry($this->actor(), (int) $id, (int) ($_GET['country_id'] ?? 0));
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
            $result = $this->publications->store($actor, $resumeId, $_POST);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        if (!($result['success'] ?? false)) {
            try {
                $data = $this->publications->form($actor, $resumeId, $this->filtersFromRequest());
            } catch (ResumeException $e) {
                SessionManager::flash('error', $e->getMessage());
                redirect(app_url('/jobseeker/resumes'));
            }
            $this->renderIndex($data, $result['errors'] ?? ['form' => [$result['message'] ?? 'Unable to save.']], $_POST, null);

            return;
        }

        $this->flashRedirect('/jobseeker/resumes/' . $resumeId . '/publications', 'success', $result['message']);
    }

    public function edit(string $id, string $publication): void
    {
        try {
            $data = $this->publications->editForm(
                $this->actor(),
                (int) $id,
                (int) $publication,
                $this->filtersFromRequest(),
                max(1, (int) ($_GET['page'] ?? 1))
            );
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/publications'));
        }

        $this->renderIndex($data, [], $data['item']->toFormArray(), (int) $publication);
    }

    public function update(string $id, string $publication): void
    {
        $actor = $this->actor();
        $resumeId = (int) $id;
        $publicationId = (int) $publication;

        try {
            $result = $this->publications->update($actor, $resumeId, $publicationId, $_POST);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        if (!($result['success'] ?? false)) {
            try {
                $data = $this->publications->editForm($actor, $resumeId, $publicationId);
            } catch (ResumeException $e) {
                SessionManager::flash('error', $e->getMessage());
                redirect(app_url('/jobseeker/resumes/' . $resumeId . '/publications'));
            }
            $this->renderIndex(
                $data,
                $result['errors'] ?? ['form' => [$result['message'] ?? 'Unable to save.']],
                array_merge($data['item']->toFormArray(), $_POST),
                $publicationId
            );

            return;
        }

        $this->flashRedirect('/jobseeker/resumes/' . $resumeId . '/publications', 'success', $result['message']);
    }

    public function destroy(string $id, string $publication): void
    {
        try {
            $result = $this->publications->delete($this->actor(), (int) $id, (int) $publication);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }
        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/publications', $type, $result['message']);
    }

    public function restore(string $id, string $publication): void
    {
        try {
            $result = $this->publications->restore($this->actor(), (int) $id, (int) $publication);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }
        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/publications', $type, $result['message']);
    }

    public function reorder(string $id): void
    {
        try {
            $result = $this->publications->reorder($this->actor(), (int) $id, $_POST);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }
        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/publications', $type, $result['message']);
    }

    public function uploadDocument(string $id, string $publication): void
    {
        $file = $_FILES['document'] ?? [];
        try {
            $result = $this->publications->uploadDocument(
                $this->actor(),
                (int) $id,
                (int) $publication,
                is_array($file) ? $file : []
            );
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }
        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/publications/' . (int) $publication . '/edit',
            $type,
            $result['message']
        );
    }

    public function removeDocument(string $id, string $publication): void
    {
        try {
            $result = $this->publications->removeDocument($this->actor(), (int) $id, (int) $publication);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }
        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/publications/' . (int) $publication . '/edit',
            $type,
            $result['message']
        );
    }

    public function download(string $id, string $publication): void
    {
        try {
            $file = $this->publications->documentDownload($this->actor(), (int) $id, (int) $publication);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        if ($file === null) {
            SessionManager::flash('error', 'Document not found.');
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/publications'));
        }

        $mime = mime_content_type($file['path']) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . rawurlencode($file['name']) . '"');
        header('Content-Length: ' . (string) filesize($file['path']));
        readfile($file['path']);
        exit;
    }

    /**
     * @return array<string, mixed>
     */
    private function filtersFromRequest(): array
    {
        return [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'publication_type' => trim((string) ($_GET['publication_type'] ?? '')),
            'publication_year' => trim((string) ($_GET['publication_year'] ?? '')),
            'is_peer_reviewed' => trim((string) ($_GET['is_peer_reviewed'] ?? '')),
            'is_featured' => trim((string) ($_GET['is_featured'] ?? '')),
            'visibility' => trim((string) ($_GET['visibility'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
            'country_id' => trim((string) ($_GET['country_id'] ?? '')),
            'sort' => trim((string) ($_GET['sort'] ?? 'sort_order')),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, list<string>>  $errors
     * @param  array<string, mixed>  $old
     */
    private function renderIndex(array $data, array $errors, array $old, ?int $editingId): void
    {
        $this->dashboard('jobseeker/pages/resumes/publications', [
            'title' => $editingId ? 'Edit publication' : 'Publications & research',
            'activeNav' => 'resumes',
            'resume' => $data['resume'],
            'items' => $data['items'],
            'deleted' => $data['deleted'] ?? [],
            'completion' => $data['completion'],
            'canEdit' => $data['can_edit'],
            'types' => $data['types'],
            'visibilities' => $data['visibilities'],
            'statuses' => $data['statuses'],
            'sorts' => $data['sorts'],
            'projects' => $data['projects'],
            'countries' => $data['countries'],
            'cities' => $data['cities'],
            'filters' => $data['filters'],
            'pagination' => $data['pagination'],
            'searchUrl' => app_url($data['search_url']),
            'citiesUrl' => app_url($data['cities_url']),
            'editingItem' => $data['item'] ?? null,
            'errors' => $errors,
            'old' => $old,
            'editingId' => $editingId,
            'resumeSection' => 'publications',
        ]);
    }
}

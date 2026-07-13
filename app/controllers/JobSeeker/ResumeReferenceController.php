<?php

declare(strict_types=1);

namespace App\Controllers\JobSeeker;

use JobVisa\App\Domain\Resume\Exceptions\ResumeException;
use JobVisa\App\Domain\Resume\Services\ResumeReferenceService;
use JobVisa\App\Security\SessionManager;

/**
 * Resume builder — professional references section.
 */
final class ResumeReferenceController extends JobSeekerController
{
    private ResumeReferenceService $references;

    public function __construct()
    {
        parent::__construct();
        $this->references = container(ResumeReferenceService::class);
    }

    public function index(string $id): void
    {
        $filters = $this->filtersFromRequest();
        $page = max(1, (int) ($_GET['page'] ?? 1));

        try {
            $data = $this->references->form($this->actor(), (int) $id, $filters, $page);
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
            $results = $this->references->search($this->actor(), (int) $id, trim((string) ($_GET['q'] ?? '')));
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
            $results = $this->references->citiesForCountry($this->actor(), (int) $id, (int) ($_GET['country_id'] ?? 0));
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
            $result = $this->references->store($actor, $resumeId, $_POST);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        if (!($result['success'] ?? false)) {
            try {
                $data = $this->references->form($actor, $resumeId, $this->filtersFromRequest());
            } catch (ResumeException $e) {
                SessionManager::flash('error', $e->getMessage());
                redirect(app_url('/jobseeker/resumes'));
            }
            $this->renderIndex($data, $result['errors'] ?? ['form' => [$result['message'] ?? 'Unable to save.']], $_POST, null);

            return;
        }

        $this->flashRedirect('/jobseeker/resumes/' . $resumeId . '/references', 'success', $result['message']);
    }

    public function edit(string $id, string $reference): void
    {
        try {
            $data = $this->references->editForm(
                $this->actor(),
                (int) $id,
                (int) $reference,
                $this->filtersFromRequest(),
                max(1, (int) ($_GET['page'] ?? 1))
            );
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/references'));
        }

        $this->renderIndex($data, [], $data['item']->toFormArray(), (int) $reference);
    }

    public function update(string $id, string $reference): void
    {
        $actor = $this->actor();
        $resumeId = (int) $id;
        $referenceId = (int) $reference;

        try {
            $result = $this->references->update($actor, $resumeId, $referenceId, $_POST);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        if (!($result['success'] ?? false)) {
            try {
                $data = $this->references->editForm($actor, $resumeId, $referenceId);
            } catch (ResumeException $e) {
                SessionManager::flash('error', $e->getMessage());
                redirect(app_url('/jobseeker/resumes/' . $resumeId . '/references'));
            }
            $this->renderIndex(
                $data,
                $result['errors'] ?? ['form' => [$result['message'] ?? 'Unable to save.']],
                array_merge($data['item']->toFormArray(), $_POST),
                $referenceId
            );

            return;
        }

        $this->flashRedirect('/jobseeker/resumes/' . $resumeId . '/references', 'success', $result['message']);
    }

    public function destroy(string $id, string $reference): void
    {
        try {
            $result = $this->references->delete($this->actor(), (int) $id, (int) $reference);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }
        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/references', $type, $result['message']);
    }

    public function restore(string $id, string $reference): void
    {
        try {
            $result = $this->references->restore($this->actor(), (int) $id, (int) $reference);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }
        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/references', $type, $result['message']);
    }

    public function reorder(string $id): void
    {
        try {
            $result = $this->references->reorder($this->actor(), (int) $id, $_POST);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }
        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/references', $type, $result['message']);
    }

    /**
     * @return array<string, mixed>
     */
    private function filtersFromRequest(): array
    {
        return [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'is_featured' => trim((string) ($_GET['is_featured'] ?? '')),
            'visibility' => trim((string) ($_GET['visibility'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
            'country_id' => trim((string) ($_GET['country_id'] ?? '')),
            'permission_to_contact' => trim((string) ($_GET['permission_to_contact'] ?? '')),
            'relationship' => trim((string) ($_GET['relationship'] ?? '')),
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
        $this->dashboard('jobseeker/pages/resumes/references', [
            'title' => $editingId ? 'Edit reference' : 'Professional references',
            'activeNav' => 'resumes',
            'resume' => $data['resume'],
            'items' => $data['items'],
            'deleted' => $data['deleted'] ?? [],
            'completion' => $data['completion'],
            'canEdit' => $data['can_edit'],
            'visibilities' => $data['visibilities'],
            'statuses' => $data['statuses'],
            'sorts' => $data['sorts'],
            'relationships' => $data['relationships'] ?? [],
            'relationshipLabels' => $data['relationship_labels'] ?? [],
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
            'resumeSection' => 'references',
        ]);
    }
}

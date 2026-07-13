<?php

declare(strict_types=1);

namespace App\Controllers\JobSeeker;

use JobVisa\App\Domain\Resume\Exceptions\ResumeException;
use JobVisa\App\Domain\Resume\Services\ResumePortfolioService;
use JobVisa\App\Security\SessionManager;

/**
 * Resume builder — professional portfolio section.
 */
final class ResumePortfolioController extends JobSeekerController
{
    private ResumePortfolioService $portfolios;

    public function __construct()
    {
        parent::__construct();
        $this->portfolios = container(ResumePortfolioService::class);
    }

    public function index(string $id): void
    {
        $filters = $this->filtersFromRequest();
        $page = max(1, (int) ($_GET['page'] ?? 1));

        try {
            $data = $this->portfolios->form($this->actor(), (int) $id, $filters, $page);
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
            $results = $this->portfolios->search($this->actor(), (int) $id, trim((string) ($_GET['q'] ?? '')));
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
            $results = $this->portfolios->citiesForCountry($this->actor(), (int) $id, (int) ($_GET['country_id'] ?? 0));
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
            $result = $this->portfolios->store($actor, $resumeId, $_POST);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        if (!($result['success'] ?? false)) {
            try {
                $data = $this->portfolios->form($actor, $resumeId, $this->filtersFromRequest());
            } catch (ResumeException $e) {
                SessionManager::flash('error', $e->getMessage());
                redirect(app_url('/jobseeker/resumes'));
            }
            $this->renderIndex($data, $result['errors'] ?? ['form' => [$result['message'] ?? 'Unable to save.']], $_POST, null);

            return;
        }

        $this->flashRedirect('/jobseeker/resumes/' . $resumeId . '/portfolio', 'success', $result['message']);
    }

    public function edit(string $id, string $portfolio): void
    {
        try {
            $data = $this->portfolios->editForm(
                $this->actor(),
                (int) $id,
                (int) $portfolio,
                $this->filtersFromRequest(),
                max(1, (int) ($_GET['page'] ?? 1))
            );
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/portfolio'));
        }

        $this->renderIndex($data, [], $data['item']->toFormArray(), (int) $portfolio);
    }

    public function update(string $id, string $portfolio): void
    {
        $actor = $this->actor();
        $resumeId = (int) $id;
        $portfolioId = (int) $portfolio;

        try {
            $result = $this->portfolios->update($actor, $resumeId, $portfolioId, $_POST);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        if (!($result['success'] ?? false)) {
            try {
                $data = $this->portfolios->editForm($actor, $resumeId, $portfolioId);
            } catch (ResumeException $e) {
                SessionManager::flash('error', $e->getMessage());
                redirect(app_url('/jobseeker/resumes/' . $resumeId . '/portfolio'));
            }
            $this->renderIndex(
                $data,
                $result['errors'] ?? ['form' => [$result['message'] ?? 'Unable to save.']],
                array_merge($data['item']->toFormArray(), $_POST),
                $portfolioId
            );

            return;
        }

        $this->flashRedirect('/jobseeker/resumes/' . $resumeId . '/portfolio', 'success', $result['message']);
    }

    public function destroy(string $id, string $portfolio): void
    {
        try {
            $result = $this->portfolios->delete($this->actor(), (int) $id, (int) $portfolio);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }
        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/portfolio', $type, $result['message']);
    }

    public function restore(string $id, string $portfolio): void
    {
        try {
            $result = $this->portfolios->restore($this->actor(), (int) $id, (int) $portfolio);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }
        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/portfolio', $type, $result['message']);
    }

    public function reorder(string $id): void
    {
        try {
            $result = $this->portfolios->reorder($this->actor(), (int) $id, $_POST);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }
        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/jobseeker/resumes/' . (int) $id . '/portfolio', $type, $result['message']);
    }

    public function uploadFeatured(string $id, string $portfolio): void
    {
        $file = $_FILES['featured_image'] ?? [];
        try {
            $result = $this->portfolios->uploadFeaturedImage(
                $this->actor(),
                (int) $id,
                (int) $portfolio,
                is_array($file) ? $file : []
            );
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }
        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/portfolio/' . (int) $portfolio . '/edit',
            $type,
            $result['message']
        );
    }

    public function removeFeatured(string $id, string $portfolio): void
    {
        try {
            $result = $this->portfolios->removeFeaturedImage($this->actor(), (int) $id, (int) $portfolio);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }
        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/portfolio/' . (int) $portfolio . '/edit',
            $type,
            $result['message']
        );
    }

    public function downloadFeatured(string $id, string $portfolio): void
    {
        try {
            $file = $this->portfolios->featuredImageDownload($this->actor(), (int) $id, (int) $portfolio);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        if ($file === null) {
            SessionManager::flash('error', 'Featured image not found.');
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/portfolio'));
        }

        $mime = mime_content_type($file['path']) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . rawurlencode($file['name']) . '"');
        header('Content-Length: ' . (string) filesize($file['path']));
        readfile($file['path']);
        exit;
    }

    public function uploadGallery(string $id, string $portfolio): void
    {
        $file = $_FILES['gallery_image'] ?? [];
        try {
            $result = $this->portfolios->uploadGalleryImage(
                $this->actor(),
                (int) $id,
                (int) $portfolio,
                is_array($file) ? $file : []
            );
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }
        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/portfolio/' . (int) $portfolio . '/edit',
            $type,
            $result['message']
        );
    }

    public function removeGallery(string $id, string $portfolio, string $image): void
    {
        try {
            $result = $this->portfolios->removeGalleryImage(
                $this->actor(),
                (int) $id,
                (int) $portfolio,
                (int) $image
            );
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }
        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/portfolio/' . (int) $portfolio . '/edit',
            $type,
            $result['message']
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function filtersFromRequest(): array
    {
        return [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'category' => trim((string) ($_GET['category'] ?? '')),
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
        $this->dashboard('jobseeker/pages/resumes/portfolio', [
            'title' => $editingId ? 'Edit portfolio item' : 'Professional portfolio',
            'activeNav' => 'resumes',
            'resume' => $data['resume'],
            'items' => $data['items'],
            'deleted' => $data['deleted'] ?? [],
            'completion' => $data['completion'],
            'canEdit' => $data['can_edit'],
            'categories' => $data['categories'],
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
            'maxGallery' => $data['max_gallery'] ?? ResumePortfolioService::MAX_GALLERY,
            'editingItem' => $data['item'] ?? null,
            'errors' => $errors,
            'old' => $old,
            'editingId' => $editingId,
            'resumeSection' => 'portfolio',
        ]);
    }
}

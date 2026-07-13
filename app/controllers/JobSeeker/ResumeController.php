<?php

declare(strict_types=1);

namespace App\Controllers\JobSeeker;

use JobVisa\App\Domain\Resume\Exceptions\ResumeException;
use JobVisa\App\Domain\Resume\Services\ResumeService;
use JobVisa\App\Security\SessionManager;

/**
 * Multi-resume dashboard (Sprint 2D.1). Existing /jobseeker/cv routes unchanged.
 */
final class ResumeController extends JobSeekerController
{
    private ResumeService $resumes;

    public function __construct()
    {
        parent::__construct();
        $this->resumes = container(ResumeService::class);
    }

    public function index(): void
    {
        $actor = $this->actor();
        $items = $this->resumes->listForActor($actor, $this->userId());

        $this->dashboard('jobseeker/pages/resumes/index', [
            'title' => 'Resumes',
            'activeNav' => 'resumes',
            'items' => $items,
        ]);
    }

    public function create(): void
    {
        $this->dashboard('jobseeker/pages/resumes/form', [
            'title' => 'Create Resume',
            'activeNav' => 'resumes',
            'mode' => 'create',
            'resume' => null,
            'errors' => [],
            'old' => ['title' => '', 'visibility' => 'employers', 'status' => 'draft'],
        ]);
    }

    public function store(): void
    {
        $result = $this->resumes->create($this->actor(), $_POST);

        if (!($result['success'] ?? false)) {
            $this->dashboard('jobseeker/pages/resumes/form', [
                'title' => 'Create Resume',
                'activeNav' => 'resumes',
                'mode' => 'create',
                'resume' => null,
                'errors' => $result['errors'] ?? ['form' => [$result['message'] ?? 'Unable to create.']],
                'old' => $_POST,
            ]);

            return;
        }

        $this->flashRedirect('/jobseeker/resumes', 'success', $result['message']);
    }

    public function show(string $id): void
    {
        try {
            $resume = $this->resumes->get($this->actor(), (int) $id);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $this->dashboard('jobseeker/pages/resumes/show', [
            'title' => $resume->title,
            'activeNav' => 'resumes',
            'resume' => $resume,
        ]);
    }

    public function edit(string $id): void
    {
        try {
            $resume = $this->resumes->get($this->actor(), (int) $id);
        } catch (ResumeException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $this->dashboard('jobseeker/pages/resumes/form', [
            'title' => 'Edit Resume',
            'activeNav' => 'resumes',
            'mode' => 'edit',
            'resume' => $resume,
            'errors' => [],
            'old' => $resume->toArray(),
        ]);
    }

    public function update(string $id): void
    {
        $result = $this->resumes->update($this->actor(), (int) $id, $_POST);

        if (!($result['success'] ?? false)) {
            try {
                $resume = $this->resumes->get($this->actor(), (int) $id);
            } catch (ResumeException) {
                $resume = null;
            }

            $this->dashboard('jobseeker/pages/resumes/form', [
                'title' => 'Edit Resume',
                'activeNav' => 'resumes',
                'mode' => 'edit',
                'resume' => $resume,
                'errors' => $result['errors'] ?? ['form' => [$result['message'] ?? 'Unable to update.']],
                'old' => $_POST,
            ]);

            return;
        }

        $this->flashRedirect('/jobseeker/resumes/' . (int) $id, 'success', $result['message']);
    }

    public function publish(string $id): void
    {
        $result = $this->resumes->publish($this->actor(), (int) $id);
        $this->flashRedirect('/jobseeker/resumes', ($result['success'] ?? false) ? 'success' : 'error', $result['message']);
    }

    public function draft(string $id): void
    {
        $result = $this->resumes->draft($this->actor(), (int) $id);
        $this->flashRedirect('/jobseeker/resumes', ($result['success'] ?? false) ? 'success' : 'error', $result['message']);
    }

    public function makeDefault(string $id): void
    {
        $result = $this->resumes->setDefault($this->actor(), (int) $id);
        $this->flashRedirect('/jobseeker/resumes', ($result['success'] ?? false) ? 'success' : 'error', $result['message']);
    }

    public function destroy(string $id): void
    {
        $result = $this->resumes->delete($this->actor(), (int) $id);
        $this->flashRedirect('/jobseeker/resumes', ($result['success'] ?? false) ? 'success' : 'error', $result['message']);
    }
}

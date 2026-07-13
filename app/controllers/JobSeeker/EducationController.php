<?php

declare(strict_types=1);

namespace App\Controllers\JobSeeker;

use JobVisa\App\JobSeeker\EducationService;

final class EducationController extends JobSeekerController
{
    public function index(): void
    {
        $items = container(EducationService::class)->list($this->actor(), $this->userId());
        $this->dashboard('jobseeker/pages/education', [
            'title' => 'Education',
            'activeNav' => 'education',
            'items' => $items,
            'edit' => null,
            'errors' => [],
        ]);
    }

    public function store(): void
    {
        $result = container(EducationService::class)->store($this->actor(), $this->userId(), $_POST);

        if (!($result['success'] ?? false)) {
            $this->dashboard('jobseeker/pages/education', [
                'title' => 'Education',
                'activeNav' => 'education',
                'items' => container(EducationService::class)->list($this->actor(), $this->userId()),
                'edit' => null,
                'errors' => $result['errors'] ?? ['form' => [$result['message']]],
                'old' => $_POST,
            ]);

            return;
        }

        $this->flashRedirect('/jobseeker/education', 'success', $result['message']);
    }

    public function update(string $id): void
    {
        $result = container(EducationService::class)->update($this->actor(), $this->userId(), (int) $id, $_POST);
        $this->flashRedirect('/jobseeker/education', ($result['success'] ?? false) ? 'success' : 'error', $result['message']);
    }

    public function destroy(string $id): void
    {
        $result = container(EducationService::class)->delete($this->actor(), $this->userId(), (int) $id);
        $this->flashRedirect('/jobseeker/education', ($result['success'] ?? false) ? 'success' : 'error', $result['message']);
    }
}

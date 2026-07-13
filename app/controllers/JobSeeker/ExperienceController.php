<?php

declare(strict_types=1);

namespace App\Controllers\JobSeeker;

use JobVisa\App\JobSeeker\ExperienceService;
use JobVisa\App\Repositories\Contracts\LocationRepositoryInterface;

final class ExperienceController extends JobSeekerController
{
    public function index(): void
    {
        $this->dashboard('jobseeker/pages/experience', [
            'title' => 'Experience',
            'activeNav' => 'experience',
            'items' => container(ExperienceService::class)->list($this->actor(), $this->userId()),
            'countries' => container(LocationRepositoryInterface::class)->listCountries(),
            'errors' => [],
        ]);
    }

    public function store(): void
    {
        $result = container(ExperienceService::class)->store($this->actor(), $this->userId(), $_POST);

        if (!($result['success'] ?? false)) {
            $this->dashboard('jobseeker/pages/experience', [
                'title' => 'Experience',
                'activeNav' => 'experience',
                'items' => container(ExperienceService::class)->list($this->actor(), $this->userId()),
                'countries' => container(LocationRepositoryInterface::class)->listCountries(),
                'errors' => $result['errors'] ?? ['form' => [$result['message']]],
                'old' => $_POST,
            ]);

            return;
        }

        $this->flashRedirect('/jobseeker/experience', 'success', $result['message']);
    }

    public function update(string $id): void
    {
        $result = container(ExperienceService::class)->update($this->actor(), $this->userId(), (int) $id, $_POST);
        $this->flashRedirect('/jobseeker/experience', ($result['success'] ?? false) ? 'success' : 'error', $result['message']);
    }

    public function destroy(string $id): void
    {
        $result = container(ExperienceService::class)->delete($this->actor(), $this->userId(), (int) $id);
        $this->flashRedirect('/jobseeker/experience', ($result['success'] ?? false) ? 'success' : 'error', $result['message']);
    }
}

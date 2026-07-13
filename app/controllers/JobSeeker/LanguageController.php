<?php

declare(strict_types=1);

namespace App\Controllers\JobSeeker;

use JobVisa\App\JobSeeker\LanguageService;

final class LanguageController extends JobSeekerController
{
    public function index(): void
    {
        $page = container(LanguageService::class)->page($this->actor(), $this->userId());
        $this->dashboard('jobseeker/pages/languages', [
            'title' => 'Languages',
            'activeNav' => 'languages',
            'items' => $page['items'],
            'catalog' => $page['catalog'],
            'errors' => [],
        ]);
    }

    public function store(): void
    {
        $result = container(LanguageService::class)->store($this->actor(), $this->userId(), $_POST);

        if (!($result['success'] ?? false)) {
            $page = container(LanguageService::class)->page($this->actor(), $this->userId());
            $this->dashboard('jobseeker/pages/languages', [
                'title' => 'Languages',
                'activeNav' => 'languages',
                'items' => $page['items'],
                'catalog' => $page['catalog'],
                'errors' => $result['errors'] ?? ['form' => [$result['message']]],
                'old' => $_POST,
            ]);

            return;
        }

        $this->flashRedirect('/jobseeker/languages', 'success', $result['message']);
    }

    public function update(string $id): void
    {
        $result = container(LanguageService::class)->update($this->actor(), $this->userId(), (int) $id, $_POST);
        $this->flashRedirect('/jobseeker/languages', ($result['success'] ?? false) ? 'success' : 'error', $result['message']);
    }

    public function destroy(string $id): void
    {
        $result = container(LanguageService::class)->delete($this->actor(), $this->userId(), (int) $id);
        $this->flashRedirect('/jobseeker/languages', ($result['success'] ?? false) ? 'success' : 'error', $result['message']);
    }
}

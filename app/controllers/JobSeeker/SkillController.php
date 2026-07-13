<?php

declare(strict_types=1);

namespace App\Controllers\JobSeeker;

use JobVisa\App\JobSeeker\SkillService;

final class SkillController extends JobSeekerController
{
    public function index(): void
    {
        $page = container(SkillService::class)->page($this->actor(), $this->userId());
        $this->dashboard('jobseeker/pages/skills', [
            'title' => 'Skills',
            'activeNav' => 'skills',
            'items' => $page['items'],
            'catalog' => $page['catalog'],
            'errors' => [],
        ]);
    }

    public function store(): void
    {
        $result = container(SkillService::class)->store($this->actor(), $this->userId(), $_POST);

        if (!($result['success'] ?? false)) {
            $page = container(SkillService::class)->page($this->actor(), $this->userId());
            $this->dashboard('jobseeker/pages/skills', [
                'title' => 'Skills',
                'activeNav' => 'skills',
                'items' => $page['items'],
                'catalog' => $page['catalog'],
                'errors' => $result['errors'] ?? ['form' => [$result['message']]],
                'old' => $_POST,
            ]);

            return;
        }

        $this->flashRedirect('/jobseeker/skills', 'success', $result['message']);
    }

    public function destroy(string $id): void
    {
        $result = container(SkillService::class)->delete($this->actor(), $this->userId(), (int) $id);
        $this->flashRedirect('/jobseeker/skills', ($result['success'] ?? false) ? 'success' : 'error', $result['message']);
    }
}

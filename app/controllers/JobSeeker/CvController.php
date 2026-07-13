<?php

declare(strict_types=1);

namespace App\Controllers\JobSeeker;

use JobVisa\App\JobSeeker\CvService;
use JobVisa\App\Security\SessionManager;

final class CvController extends JobSeekerController
{
    public function index(): void
    {
        $this->dashboard('jobseeker/pages/cv', [
            'title' => 'CV',
            'activeNav' => 'cv',
            'resume' => container(CvService::class)->current($this->actor(), $this->userId()),
        ]);
    }

    public function upload(): void
    {
        $file = $_FILES['cv'] ?? null;

        if (!is_array($file)) {
            SessionManager::flash('error', 'Please choose a PDF file.');
            redirect(app_url('/jobseeker/cv'));
        }

        $result = container(CvService::class)->upload($this->actor(), $this->userId(), $file);
        $this->flashRedirect('/jobseeker/cv', ($result['success'] ?? false) ? 'success' : 'error', $result['message']);
    }

    public function download(): void
    {
        $result = container(CvService::class)->download($this->actor(), $this->userId());

        if (!($result['success'] ?? false)) {
            SessionManager::flash('error', $result['message']);
            redirect(app_url('/jobseeker/cv'));
        }

        header('Content-Type: ' . $result['mime']);
        header('Content-Disposition: attachment; filename="' . $result['name'] . '"');
        header('Content-Length: ' . (string) filesize($result['path']));
        readfile($result['path']);
        exit;
    }

    public function destroy(): void
    {
        $result = container(CvService::class)->delete($this->actor(), $this->userId());
        $this->flashRedirect('/jobseeker/cv', ($result['success'] ?? false) ? 'success' : 'error', $result['message']);
    }
}

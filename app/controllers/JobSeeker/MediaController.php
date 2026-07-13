<?php

declare(strict_types=1);

namespace App\Controllers\JobSeeker;

use JobVisa\App\Support\FileStorage;

final class MediaController extends JobSeekerController
{
    public function avatar(): void
    {
        $actor = $this->actor();
        $userId = $this->userId();
        $profile = container(\JobVisa\App\Repositories\Contracts\UserProfileRepositoryInterface::class)->findByUserId($userId);
        $path = is_array($profile) ? ($profile['avatar_path'] ?? null) : null;

        if (!is_string($path) || $path === '') {
            http_response_code(404);
            echo 'Not found';
            return;
        }

        if (!container(\JobVisa\App\JobSeeker\ProfileAccess::class)->canView($actor, $userId)) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        $absolute = container(FileStorage::class)->absolutePath($path);

        if (!is_file($absolute)) {
            http_response_code(404);
            echo 'Not found';
            return;
        }

        $mime = mime_content_type($absolute) ?: 'image/jpeg';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($absolute));
        header('Cache-Control: private, max-age=3600');
        readfile($absolute);
        exit;
    }
}

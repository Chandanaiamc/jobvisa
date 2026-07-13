<?php

declare(strict_types=1);

namespace JobVisa\App\JobSeeker;

use JobVisa\App\Repositories\Contracts\ResumeRepositoryInterface;
use JobVisa\App\Support\FileStorage;
use RuntimeException;

final class CvService
{
    public function __construct(
        private readonly ResumeRepositoryInterface $resumes,
        private readonly ProfileCompletenessService $completeness,
        private readonly FileStorage $storage,
        private readonly ProfileAccess $access
    ) {
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>|null
     */
    public function current(array $actor, int $userId): ?array
    {
        $this->assertView($actor, $userId);

        return $this->resumes->ensurePrimary($userId);
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $file
     * @return array{success: bool, message: string}
     */
    public function upload(array $actor, int $userId, array $file): array
    {
        $this->assertEdit($actor, $userId);
        $resume = $this->resumes->ensurePrimary($userId);
        $old = $resume['file_path'] ?? null;

        try {
            $path = $this->storage->storeUpload(
                $file,
                'cvs/' . $userId,
                'cv',
                (array) config('uploads.cv_mimes', ['application/pdf']),
                (int) config('uploads.max_cv_bytes', 5_242_880)
            );
        } catch (RuntimeException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        $absolute = $this->storage->absolutePath($path);
        $size = is_file($absolute) ? (int) filesize($absolute) : null;
        $this->resumes->updateFile((int) $resume['id'], $path, 'application/pdf', $size);

        if (is_string($old) && $old !== '' && $old !== $path) {
            $this->storage->delete($old);
        }

        $this->completeness->evaluate($userId);

        return ['success' => true, 'message' => 'CV uploaded successfully.'];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, path?: string, mime?: string, name?: string}
     */
    public function download(array $actor, int $userId): array
    {
        $this->assertView($actor, $userId);
        $resume = $this->resumes->ensurePrimary($userId);
        $path = $resume['file_path'] ?? null;

        if (!is_string($path) || $path === '') {
            return ['success' => false, 'message' => 'No CV on file.'];
        }

        $absolute = $this->storage->absolutePath($path);

        if (!is_file($absolute)) {
            return ['success' => false, 'message' => 'CV file is missing.'];
        }

        return [
            'success' => true,
            'message' => 'OK',
            'path' => $absolute,
            'mime' => (string) ($resume['file_mime'] ?? 'application/pdf'),
            'name' => 'JobVisa-CV-' . $userId . '.pdf',
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function delete(array $actor, int $userId): array
    {
        $this->assertEdit($actor, $userId);
        $resume = $this->resumes->ensurePrimary($userId);
        $old = $resume['file_path'] ?? null;

        $this->resumes->updateFile((int) $resume['id'], null, null, null);

        if (is_string($old) && $old !== '') {
            $this->storage->delete($old);
        }

        $this->completeness->evaluate($userId);

        return ['success' => true, 'message' => 'CV deleted.'];
    }

    /** @param array<string, mixed> $actor */
    private function assertView(array $actor, int $userId): void
    {
        if (!$this->access->canView($actor, $userId)) {
            throw new RuntimeException('Forbidden');
        }
    }

    /** @param array<string, mixed> $actor */
    private function assertEdit(array $actor, int $userId): void
    {
        if (!$this->access->canEdit($actor, $userId)) {
            throw new RuntimeException('Forbidden');
        }
    }
}

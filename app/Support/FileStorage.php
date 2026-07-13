<?php

declare(strict_types=1);

namespace JobVisa\App\Support;

use JobVisa\App\Logging\Logger;
use RuntimeException;

/**
 * Local disk file storage for avatars and CV PDFs.
 */
final class FileStorage
{
    public function root(): string
    {
        $relative = (string) config('uploads.disk_path', 'storage/uploads');
        $path = base_path($relative);

        if (!is_dir($path) && !@mkdir($path, 0755, true) && !is_dir($path)) {
            throw new RuntimeException('Unable to create upload directory.');
        }

        return $path;
    }

    /**
     * Store an uploaded file under a relative key. Returns relative path from project root.
     *
     * @param  array{tmp_name: string, name: string, type: string, size: int, error: int}  $file
     */
    public function storeUpload(array $file, string $directory, string $basename, array $allowedMimes, int $maxBytes): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('File upload failed.');
        }

        $size = (int) ($file['size'] ?? 0);

        if ($size < 1 || $size > $maxBytes) {
            throw new RuntimeException('File size is not allowed.');
        }

        $tmp = (string) ($file['tmp_name'] ?? '');

        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new RuntimeException('Invalid upload.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($tmp);

        if (!in_array($mime, $allowedMimes, true)) {
            throw new RuntimeException('File type is not allowed.');
        }

        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            default => 'bin',
        };

        $relativeDir = trim(str_replace(['\\', '..'], ['/', ''], $directory), '/');
        $absoluteDir = $this->root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);

        if (!is_dir($absoluteDir) && !@mkdir($absoluteDir, 0755, true) && !is_dir($absoluteDir)) {
            throw new RuntimeException('Unable to create upload subdirectory.');
        }

        $safeBase = preg_replace('/[^a-zA-Z0-9_-]/', '', $basename) ?: 'file';
        $filename = $safeBase . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $absolute = $absoluteDir . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($tmp, $absolute)) {
            throw new RuntimeException('Unable to save uploaded file.');
        }

        $relative = 'storage/uploads/' . $relativeDir . '/' . $filename;

        Logger::info('File stored', ['path' => $relative, 'mime' => $mime, 'size' => $size]);

        return $relative;
    }

    public function absolutePath(string $relativePath): string
    {
        $relativePath = ltrim(str_replace(['\\', '..'], ['/', ''], $relativePath), '/');

        return base_path($relativePath);
    }

    public function delete(string $relativePath): void
    {
        $absolute = $this->absolutePath($relativePath);

        if (is_file($absolute)) {
            @unlink($absolute);
        }
    }
}

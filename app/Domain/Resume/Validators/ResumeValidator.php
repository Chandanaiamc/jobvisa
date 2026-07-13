<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Validators;

use JobVisa\App\Domain\Resume\Entities\Resume;
use JobVisa\App\Domain\Support\AbstractValidator;

/**
 * Resume input validation (domain rules).
 */
final class ResumeValidator extends AbstractValidator
{
    /**
     * @param  array<string, mixed>|object  $input
     * @return list<string>
     */
    public function validate(array|object $input): array
    {
        $data = is_object($input) ? (array) $input : $input;
        $errors = [];

        $title = trim((string) ($data['title'] ?? ''));

        if ($title === '') {
            $errors[] = 'Title is required.';
        } elseif (mb_strlen($title) > 150) {
            $errors[] = 'Title may not exceed 150 characters.';
        }

        if (array_key_exists('status', $data) && $data['status'] !== null && $data['status'] !== '') {
            $status = (string) $data['status'];

            if (!in_array($status, [Resume::STATUS_DRAFT, Resume::STATUS_PUBLISHED], true)) {
                $errors[] = 'Status must be draft or published.';
            }
        }

        if (array_key_exists('visibility', $data) && $data['visibility'] !== null && $data['visibility'] !== '') {
            $visibility = (string) $data['visibility'];

            if (!in_array($visibility, [
                Resume::VISIBILITY_PUBLIC,
                Resume::VISIBILITY_EMPLOYERS,
                Resume::VISIBILITY_PRIVATE,
            ], true)) {
                $errors[] = 'Visibility must be public, employers, or private.';
            }
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, list<string>>
     */
    public function fieldErrors(array $input): array
    {
        $messages = $this->validate($input);
        $mapped = [];

        foreach ($messages as $message) {
            $field = 'form';

            if (str_contains(strtolower($message), 'title')) {
                $field = 'title';
            } elseif (str_contains(strtolower($message), 'status')) {
                $field = 'status';
            } elseif (str_contains(strtolower($message), 'visibility')) {
                $field = 'visibility';
            }

            $mapped[$field][] = $message;
        }

        return $mapped;
    }
}

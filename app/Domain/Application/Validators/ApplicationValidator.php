<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Application\Validators;

use JobVisa\App\Domain\Support\AbstractValidator;

/**
 * Application input validation (apply + employer status updates).
 */
final class ApplicationValidator extends AbstractValidator
{
    public const STATUSES = ['submitted', 'reviewing', 'shortlisted', 'rejected', 'hired', 'withdrawn'];

    /**
     * Employer transitions from current status.
     *
     * @var array<string, list<string>>
     */
    public const EMPLOYER_TRANSITIONS = [
        'submitted' => ['reviewing', 'shortlisted', 'rejected'],
        'reviewing' => ['shortlisted', 'rejected', 'hired'],
        'shortlisted' => ['rejected', 'hired', 'reviewing'],
        'rejected' => ['reviewing', 'shortlisted'],
        'hired' => [],
        'withdrawn' => [],
    ];

    /**
     * @param  array<string, mixed>|object  $input
     * @return list<string>
     */
    public function validate(array|object $input): array
    {
        $data = is_object($input) ? (array) $input : $input;
        $errors = [];
        $mode = (string) ($data['_mode'] ?? 'apply');

        if ($mode === 'apply') {
            if (array_key_exists('resume_id', $data) && $data['resume_id'] !== null && $data['resume_id'] !== '') {
                if ((int) $data['resume_id'] < 1) {
                    $errors[] = 'Resume id is invalid.';
                }
            }
            if (array_key_exists('cover_letter', $data) && $data['cover_letter'] !== null) {
                $letter = (string) $data['cover_letter'];
                if (mb_strlen($letter) > 10000) {
                    $errors[] = 'Cover letter may not exceed 10000 characters.';
                }
            }
        }

        if ($mode === 'status') {
            $status = (string) ($data['status'] ?? '');
            if ($status === '' || !in_array($status, self::STATUSES, true)) {
                $errors[] = 'Status is invalid.';
            }
            if (array_key_exists('employer_notes', $data) && $data['employer_notes'] !== null) {
                if (mb_strlen((string) $data['employer_notes']) > 5000) {
                    $errors[] = 'Employer notes may not exceed 5000 characters.';
                }
            }
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, list<string>>
     */
    public function fieldErrors(array $input, string $mode = 'apply'): array
    {
        $input['_mode'] = $mode;
        $mapped = [];
        foreach ($this->validate($input) as $message) {
            $lower = strtolower($message);
            $field = 'form';
            if (str_contains($lower, 'resume')) {
                $field = 'resume_id';
            } elseif (str_contains($lower, 'cover')) {
                $field = 'cover_letter';
            } elseif (str_contains($lower, 'status')) {
                $field = 'status';
            } elseif (str_contains($lower, 'notes')) {
                $field = 'employer_notes';
            }
            $mapped[$field][] = $message;
        }

        return $mapped;
    }

    public function canEmployerTransition(string $from, string $to): bool
    {
        $allowed = self::EMPLOYER_TRANSITIONS[$from] ?? null;
        if ($allowed === null) {
            return false;
        }

        return in_array($to, $allowed, true);
    }
}

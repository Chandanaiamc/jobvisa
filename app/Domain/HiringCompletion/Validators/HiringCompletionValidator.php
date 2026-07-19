<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\HiringCompletion\Validators;

use DateTimeImmutable;
use JobVisa\App\Domain\Support\AbstractValidator;

/**
 * Validation for hire completion confirm / lifecycle inputs.
 */
final class HiringCompletionValidator extends AbstractValidator
{
    public const STATUSES = ['pending', 'confirmed', 'completed', 'cancelled'];

    /** Application statuses that may become hired via offer accept. */
    public const HIREABLE_APPLICATION_STATUSES = ['shortlisted', 'reviewing', 'hired'];

    /**
     * @param  array<string, mixed>|object  $input
     * @return list<string>
     */
    public function validate(array|object $input): array
    {
        $data = is_object($input) ? (array) $input : $input;
        $errors = [];
        $mode = (string) ($data['_mode'] ?? 'confirm');

        if (in_array($mode, ['confirm', 'complete'], true)) {
            if (array_key_exists('start_date', $data) && $data['start_date'] !== null && trim((string) $data['start_date']) !== '') {
                if ($this->parseDate((string) $data['start_date']) === null) {
                    $errors[] = 'Start date must be a valid date (Y-m-d).';
                }
            }
            if (array_key_exists('notes', $data) && is_string($data['notes']) && mb_strlen($data['notes']) > 500) {
                $errors[] = 'Notes may not exceed 500 characters.';
            }
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, list<string>>
     */
    public function fieldErrors(array $input, string $mode = 'confirm'): array
    {
        $input['_mode'] = $mode;
        $mapped = [];
        foreach ($this->validate($input) as $message) {
            $lower = strtolower($message);
            $field = 'form';
            if (str_contains($lower, 'start date')) {
                $field = 'start_date';
            } elseif (str_contains($lower, 'notes')) {
                $field = 'notes';
            }
            $mapped[$field][] = $message;
        }

        return $mapped;
    }

    public function parseDate(string $value): ?string
    {
        $raw = trim($value);
        if ($raw === '') {
            return null;
        }
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $raw);
        if ($dt instanceof DateTimeImmutable && $dt->format('Y-m-d') === $raw) {
            return $raw;
        }

        return null;
    }

    public function isHireableApplicationStatus(string $status): bool
    {
        return in_array($status, self::HIREABLE_APPLICATION_STATUSES, true);
    }
}

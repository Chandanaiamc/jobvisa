<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\InterviewScheduling\Validators;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use JobVisa\App\Domain\Support\AbstractValidator;

/**
 * Validation for schedule / reschedule / status inputs.
 */
final class InterviewSchedulingValidator extends AbstractValidator
{
    public const STATUSES = ['proposed', 'confirmed', 'declined', 'cancelled', 'completed'];

    public const ACTIVE_STATUSES = ['proposed', 'confirmed'];

    public const LOCATION_TYPES = ['onsite', 'phone', 'other'];

    /**
     * @param  array<string, mixed>|object  $input
     * @return list<string>
     */
    public function validate(array|object $input): array
    {
        $data = is_object($input) ? (array) $input : $input;
        $errors = [];
        $mode = (string) ($data['_mode'] ?? 'schedule');

        if (in_array($mode, ['schedule', 'reschedule'], true)) {
            $tz = trim((string) ($data['timezone'] ?? ''));
            if ($tz === '') {
                $errors[] = 'Timezone is required.';
            } elseif (!$this->isValidTimezone($tz)) {
                $errors[] = 'Timezone must be a valid IANA timezone identifier.';
            }

            $scheduled = trim((string) ($data['scheduled_at'] ?? $data['scheduled_at_utc'] ?? ''));
            if ($scheduled === '') {
                $errors[] = 'Scheduled at is required.';
            } elseif ($tz !== '' && $this->isValidTimezone($tz)) {
                $utc = $this->parseToUtc($scheduled, $tz, !empty($data['scheduled_at_is_utc']));
                if ($utc === null) {
                    $errors[] = 'Scheduled at must be a valid datetime.';
                } elseif ($utc <= new DateTimeImmutable('now', new DateTimeZone('UTC'))) {
                    $errors[] = 'Scheduled at must be in the future (UTC).';
                }
            }

            if (array_key_exists('duration_minutes', $data) && $data['duration_minutes'] !== null && $data['duration_minutes'] !== '') {
                $mins = (int) $data['duration_minutes'];
                if ($mins < 15 || $mins > 480) {
                    $errors[] = 'Duration minutes must be between 15 and 480.';
                }
            }

            if (array_key_exists('location_type', $data) && $data['location_type'] !== null && $data['location_type'] !== '') {
                if (!in_array((string) $data['location_type'], self::LOCATION_TYPES, true)) {
                    $errors[] = 'Location type must be onsite, phone, or other.';
                }
            }

            if (array_key_exists('location_notes', $data) && is_string($data['location_notes']) && mb_strlen($data['location_notes']) > 500) {
                $errors[] = 'Location notes may not exceed 500 characters.';
            }
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, list<string>>
     */
    public function fieldErrors(array $input, string $mode = 'schedule'): array
    {
        $input['_mode'] = $mode;
        $mapped = [];
        foreach ($this->validate($input) as $message) {
            $lower = strtolower($message);
            $field = 'form';
            if (str_contains($lower, 'timezone')) {
                $field = 'timezone';
            } elseif (str_contains($lower, 'scheduled')) {
                $field = 'scheduled_at';
            } elseif (str_contains($lower, 'duration')) {
                $field = 'duration_minutes';
            } elseif (str_contains($lower, 'location type')) {
                $field = 'location_type';
            } elseif (str_contains($lower, 'location notes')) {
                $field = 'location_notes';
            }
            $mapped[$field][] = $message;
        }

        return $mapped;
    }

    public function isValidTimezone(string $timezone): bool
    {
        try {
            new DateTimeZone($timezone);

            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Parse scheduled_at as local wall time in $timezone (default) or as UTC if $asUtc.
     */
    public function parseToUtc(string $scheduledAt, string $timezone, bool $asUtc = false): ?DateTimeImmutable
    {
        $raw = trim($scheduledAt);
        if ($raw === '') {
            return null;
        }
        // Normalize trailing Z
        if (str_ends_with(strtoupper($raw), 'Z')) {
            $raw = substr($raw, 0, -1);
            $asUtc = true;
        }

        try {
            $tz = new DateTimeZone($asUtc ? 'UTC' : $timezone);
            $formats = [
                'Y-m-d\TH:i:s.u',
                'Y-m-d\TH:i:s',
                'Y-m-d H:i:s.u',
                'Y-m-d H:i:s',
                'Y-m-d\TH:i',
                'Y-m-d H:i',
            ];
            foreach ($formats as $format) {
                $dt = DateTimeImmutable::createFromFormat($format, $raw, $tz);
                if ($dt instanceof DateTimeImmutable) {
                    return $dt->setTimezone(new DateTimeZone('UTC'));
                }
            }
            $dt = new DateTimeImmutable($raw, $tz);

            return $dt->setTimezone(new DateTimeZone('UTC'));
        } catch (Exception) {
            return null;
        }
    }

    public function formatUtc(DateTimeImmutable $utc): string
    {
        return $utc->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s.v');
    }

    public function formatLocal(DateTimeImmutable $utc, string $timezone): string
    {
        try {
            return $utc->setTimezone(new DateTimeZone($timezone))->format('Y-m-d H:i:s');
        } catch (Exception) {
            return $this->formatUtc($utc);
        }
    }
}

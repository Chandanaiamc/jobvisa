<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\JobOffer\Validators;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use JobVisa\App\Domain\Support\AbstractValidator;

/**
 * Validation for create / update / lifecycle offer inputs.
 */
final class JobOfferValidator extends AbstractValidator
{
    public const STATUSES = ['draft', 'sent', 'accepted', 'declined', 'withdrawn', 'expired'];

    public const ACTIVE_STATUSES = ['draft', 'sent'];

    public const PAY_PERIODS = ['monthly', 'yearly', 'hourly', 'daily'];

    /**
     * @param  array<string, mixed>|object  $input
     * @return list<string>
     */
    public function validate(array|object $input): array
    {
        $data = is_object($input) ? (array) $input : $input;
        $errors = [];
        $mode = (string) ($data['_mode'] ?? 'create');

        if ($mode === 'create') {
            if (!array_key_exists('salary_amount', $data) || $data['salary_amount'] === null || $data['salary_amount'] === '') {
                $errors[] = 'Salary amount is required.';
            } else {
                $amount = (float) $data['salary_amount'];
                if ($amount <= 0) {
                    $errors[] = 'Salary amount must be greater than zero.';
                } elseif ($amount > 999999999999.99) {
                    $errors[] = 'Salary amount is too large.';
                }
            }

            $currency = strtoupper(trim((string) ($data['salary_currency'] ?? 'LKR')));
            if ($currency === '' || !preg_match('/^[A-Z]{3}$/', $currency)) {
                $errors[] = 'Salary currency must be a 3-letter ISO code.';
            }

            if (array_key_exists('pay_period', $data) && $data['pay_period'] !== null && $data['pay_period'] !== '') {
                if (!in_array((string) $data['pay_period'], self::PAY_PERIODS, true)) {
                    $errors[] = 'Pay period must be monthly, yearly, hourly, or daily.';
                }
            }

            if (array_key_exists('start_date', $data) && $data['start_date'] !== null && trim((string) $data['start_date']) !== '') {
                if ($this->parseDate((string) $data['start_date']) === null) {
                    $errors[] = 'Start date must be a valid date (Y-m-d).';
                }
            }

            if (array_key_exists('expires_at', $data) || array_key_exists('expires_at_utc', $data)) {
                $raw = trim((string) ($data['expires_at_utc'] ?? $data['expires_at'] ?? ''));
                if ($raw !== '') {
                    $utc = $this->parseToUtc($raw, !empty($data['expires_at_is_utc']) || array_key_exists('expires_at_utc', $data));
                    if ($utc === null) {
                        $errors[] = 'Expires at must be a valid datetime.';
                    } elseif ($utc <= new DateTimeImmutable('now', new DateTimeZone('UTC'))) {
                        $errors[] = 'Expires at must be in the future (UTC).';
                    }
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
    public function fieldErrors(array $input, string $mode = 'create'): array
    {
        $input['_mode'] = $mode;
        $mapped = [];
        foreach ($this->validate($input) as $message) {
            $lower = strtolower($message);
            $field = 'form';
            if (str_contains($lower, 'salary amount')) {
                $field = 'salary_amount';
            } elseif (str_contains($lower, 'salary currency')) {
                $field = 'salary_currency';
            } elseif (str_contains($lower, 'pay period')) {
                $field = 'pay_period';
            } elseif (str_contains($lower, 'start date')) {
                $field = 'start_date';
            } elseif (str_contains($lower, 'expires')) {
                $field = 'expires_at';
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

    /**
     * Parse expires_at as UTC (default) or as local wall time with Asia/Colombo if not flagged UTC.
     * Phase 1 stores expires_at_utc only — non-UTC inputs are interpreted as UTC unless expires_at_is_utc is false
     * and a timezone is supplied (optional).
     */
    public function parseToUtc(string $value, bool $asUtc = true, string $timezone = 'UTC'): ?DateTimeImmutable
    {
        $raw = trim($value);
        if ($raw === '') {
            return null;
        }
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
                'Y-m-d',
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

    public function isExpired(?string $expiresAtUtc, string $status = 'sent'): bool
    {
        if ($status !== 'sent' || $expiresAtUtc === null || trim($expiresAtUtc) === '') {
            return false;
        }
        $utc = $this->parseToUtc($expiresAtUtc, true);
        if ($utc === null) {
            return false;
        }

        return $utc <= new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}

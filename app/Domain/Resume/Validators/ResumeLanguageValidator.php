<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Validators;

/**
 * Domain validation for resume languages (CEFR + certificates).
 */
final class ResumeLanguageValidator
{
    public const CEFR = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

    public const CERTIFICATE_TYPES = [
        'IELTS',
        'TOEFL',
        'PTE',
        'HSK',
        'JLPT',
        'TOPIK',
        'Other',
    ];

    public const STATUSES = ['active', 'archived'];

    /**
     * @param  array<string, mixed>  $input
     * @param  callable(int): bool|null  $languageExists
     * @return array<string, list<string>>
     */
    public function validate(array $input, ?callable $languageExists = null, bool $requireLanguageId = true): array
    {
        $errors = [];

        $languageId = (int) ($input['language_id'] ?? 0);
        if ($requireLanguageId) {
            if ($languageId < 1) {
                $errors['language_id'][] = 'Select a language from the catalogue.';
            } elseif ($languageExists !== null && !$languageExists($languageId)) {
                $errors['language_id'][] = 'Language is invalid or inactive.';
            }
        }

        foreach (['speaking', 'reading', 'writing', 'listening'] as $skill) {
            $level = strtoupper(trim((string) ($input[$skill] ?? '')));
            if ($level === '') {
                $errors[$skill][] = ucfirst($skill) . ' proficiency is required.';
            } elseif (!in_array($level, self::CEFR, true)) {
                $errors[$skill][] = ucfirst($skill) . ' must be a CEFR level (A1–C2).';
            }
        }

        $certType = trim((string) ($input['certificate_type'] ?? ''));
        if ($certType !== '' && !in_array($certType, self::CERTIFICATE_TYPES, true)) {
            $errors['certificate_type'][] = 'Certificate type is not allowed.';
        }

        $score = trim((string) ($input['certificate_score'] ?? ''));
        if ($score !== '' && mb_strlen($score) > 32) {
            $errors['certificate_score'][] = 'Certificate score may not exceed 32 characters.';
        }

        $issued = trim((string) ($input['certificate_issued_at'] ?? ''));
        $expires = trim((string) ($input['certificate_expires_at'] ?? ''));

        if ($issued !== '' && !$this->isDate($issued)) {
            $errors['certificate_issued_at'][] = 'Certificate issue date is invalid.';
        }
        if ($expires !== '' && !$this->isDate($expires)) {
            $errors['certificate_expires_at'][] = 'Certificate expiry date is invalid.';
        }
        if ($issued !== '' && $expires !== '' && $this->isDate($issued) && $this->isDate($expires) && $expires < $issued) {
            $errors['certificate_expires_at'][] = 'Expiry date cannot be before issue date.';
        }

        $status = trim((string) ($input['status'] ?? 'active'));
        if ($status !== '' && !in_array($status, self::STATUSES, true)) {
            $errors['status'][] = 'Status is invalid.';
        }

        if (isset($input['sort_order']) && $input['sort_order'] !== '') {
            if (!is_numeric($input['sort_order']) || (int) $input['sort_order'] < 0 || (int) $input['sort_order'] > 9999) {
                $errors['sort_order'][] = 'Sort order must be between 0 and 9999.';
            }
        }

        return $errors;
    }

    private function isDate(string $value): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }
        [$y, $m, $d] = array_map('intval', explode('-', $value));

        return checkdate($m, $d, $y);
    }
}

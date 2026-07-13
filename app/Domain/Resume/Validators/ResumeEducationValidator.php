<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Validators;

/**
 * Domain validation for resume education records.
 */
final class ResumeEducationValidator
{
    public const QUALIFICATION_TYPES = [
        'high_school',
        'certificate',
        'diploma',
        'associate',
        'bachelor',
        'master',
        'doctorate',
        'vocational',
        'professional',
        'other',
    ];

    public const STATUSES = ['active', 'archived'];

    /**
     * @param  array<string, mixed>  $input
     * @param  callable(int): bool|null  $countryExists
     * @return array<string, list<string>>
     */
    public function validate(array $input, ?callable $countryExists = null): array
    {
        $errors = [];

        $institution = trim((string) ($input['institution'] ?? ''));
        if ($institution === '') {
            $errors['institution'][] = 'Institution is required.';
        } elseif (mb_strlen($institution) > 200) {
            $errors['institution'][] = 'Institution may not exceed 200 characters.';
        }

        $degree = trim((string) ($input['degree'] ?? ''));
        if ($degree === '') {
            $errors['degree'][] = 'Qualification title is required.';
        } elseif (mb_strlen($degree) > 150) {
            $errors['degree'][] = 'Qualification title may not exceed 150 characters.';
        }

        $type = trim((string) ($input['qualification_type'] ?? ''));
        if ($type === '') {
            $errors['qualification_type'][] = 'Qualification type is required.';
        } elseif (!in_array($type, self::QUALIFICATION_TYPES, true)) {
            $errors['qualification_type'][] = 'Qualification type is invalid.';
        }

        $school = trim((string) ($input['school'] ?? ''));
        if ($school !== '' && mb_strlen($school) > 200) {
            $errors['school'][] = 'School name may not exceed 200 characters.';
        }

        $field = trim((string) ($input['field_of_study'] ?? ''));
        if ($field !== '' && mb_strlen($field) > 150) {
            $errors['field_of_study'][] = 'Field of study may not exceed 150 characters.';
        }

        $grade = trim((string) ($input['grade'] ?? ''));
        if ($grade !== '') {
            if (mb_strlen($grade) > 64) {
                $errors['grade'][] = 'Grade / GPA may not exceed 64 characters.';
            } elseif (!preg_match('/^[\w.\-+\/ %]+$/u', $grade)) {
                $errors['grade'][] = 'Grade / GPA contains invalid characters.';
            }
        }

        $city = trim((string) ($input['city'] ?? ''));
        if ($city !== '' && mb_strlen($city) > 120) {
            $errors['city'][] = 'City may not exceed 120 characters.';
        }

        $countryId = $input['country_id'] ?? null;
        if ($countryId !== null && $countryId !== '') {
            $cid = (int) $countryId;
            if ($cid < 1 || ($countryExists !== null && !$countryExists($cid))) {
                $errors['country_id'][] = 'Country is invalid.';
            }
        }

        $start = trim((string) ($input['start_date'] ?? ''));
        if ($start === '') {
            $errors['start_date'][] = 'Start date is required.';
        } elseif (!$this->isDate($start)) {
            $errors['start_date'][] = 'Start date is invalid.';
        }

        $isCurrent = !empty($input['is_current']);
        $end = trim((string) ($input['end_date'] ?? ''));
        if (!$isCurrent) {
            if ($end === '') {
                $errors['end_date'][] = 'End date is required unless currently studying.';
            } elseif (!$this->isDate($end)) {
                $errors['end_date'][] = 'End date is invalid.';
            }
        } elseif ($end !== '' && !$this->isDate($end)) {
            $errors['end_date'][] = 'End date is invalid.';
        }

        if ($start !== '' && $end !== '' && $this->isDate($start) && $this->isDate($end) && $end < $start) {
            $errors['end_date'][] = 'End date cannot be before start date.';
        }

        $description = trim((string) ($input['description'] ?? ''));
        if ($description !== '' && mb_strlen($description) > 5000) {
            $errors['description'][] = 'Description may not exceed 5000 characters.';
        }

        $status = trim((string) ($input['status'] ?? 'active'));
        if ($status !== '' && !in_array($status, self::STATUSES, true)) {
            $errors['status'][] = 'Status is invalid.';
        }

        if (isset($input['sort_order']) && $input['sort_order'] !== '') {
            if (!is_numeric($input['sort_order']) || (int) $input['sort_order'] < 0 || (int) $input['sort_order'] > 9999) {
                $errors['sort_order'][] = 'Display order must be between 0 and 9999.';
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

<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Validators;

/**
 * Domain validation for resume work experience records.
 */
final class ResumeExperienceValidator
{
    public const EMPLOYMENT_TYPES = [
        'full_time',
        'part_time',
        'contract',
        'temporary',
        'internship',
        'apprenticeship',
        'freelance',
        'self_employed',
        'volunteer',
    ];

    public const STATUSES = ['active', 'archived'];

    /**
     * @param  array<string, mixed>  $input
     * @param  callable(int): bool|null  $countryExists
     * @param  list<int>|null  $allowedSkillIds
     * @return array<string, list<string>>
     */
    public function validate(array $input, ?callable $countryExists = null, ?array $allowedSkillIds = null): array
    {
        $errors = [];

        $company = trim((string) ($input['company_name'] ?? ''));
        if ($company === '') {
            $errors['company_name'][] = 'Company name is required.';
        } elseif (mb_strlen($company) > 200) {
            $errors['company_name'][] = 'Company name may not exceed 200 characters.';
        }

        $title = trim((string) ($input['job_title'] ?? ''));
        if ($title === '') {
            $errors['job_title'][] = 'Job title is required.';
        } elseif (mb_strlen($title) > 150) {
            $errors['job_title'][] = 'Job title may not exceed 150 characters.';
        }

        $type = trim((string) ($input['employment_type'] ?? ''));
        if ($type === '') {
            $errors['employment_type'][] = 'Employment type is required.';
        } elseif (!in_array($type, self::EMPLOYMENT_TYPES, true)) {
            $errors['employment_type'][] = 'Employment type is not allowed.';
        }

        $industry = trim((string) ($input['industry'] ?? ''));
        if ($industry !== '' && mb_strlen($industry) > 150) {
            $errors['industry'][] = 'Industry may not exceed 150 characters.';
        }

        $city = trim((string) ($input['city'] ?? ''));
        if ($city !== '' && mb_strlen($city) > 120) {
            $errors['city'][] = 'City may not exceed 120 characters.';
        }

        $countryRaw = $input['country_id'] ?? null;
        if ($countryRaw === null || $countryRaw === '') {
            $errors['country_id'][] = 'Country is required.';
        } else {
            $cid = (int) $countryRaw;
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
                $errors['end_date'][] = 'End date is required unless currently working.';
            } elseif (!$this->isDate($end)) {
                $errors['end_date'][] = 'End date is invalid.';
            }
        } elseif ($end !== '' && !$this->isDate($end)) {
            $errors['end_date'][] = 'End date is invalid.';
        }

        if ($start !== '' && $end !== '' && $this->isDate($start) && $this->isDate($end) && $end < $start) {
            $errors['end_date'][] = 'End date cannot be before start date.';
        }

        $responsibilities = trim((string) ($input['responsibilities'] ?? $input['description'] ?? ''));
        if ($responsibilities !== '' && mb_strlen($responsibilities) > 8000) {
            $errors['responsibilities'][] = 'Responsibilities may not exceed 8000 characters.';
        }

        $achievements = trim((string) ($input['achievements'] ?? ''));
        if ($achievements !== '' && mb_strlen($achievements) > 8000) {
            $errors['achievements'][] = 'Achievements may not exceed 8000 characters.';
        }

        $reason = trim((string) ($input['reason_for_leaving'] ?? ''));
        if ($reason !== '' && mb_strlen($reason) > 2000) {
            $errors['reason_for_leaving'][] = 'Reason for leaving may not exceed 2000 characters.';
        }

        $supervisor = trim((string) ($input['supervisor_name'] ?? ''));
        if ($supervisor !== '' && mb_strlen($supervisor) > 150) {
            $errors['supervisor_name'][] = 'Supervisor name may not exceed 150 characters.';
        }

        $contact = trim((string) ($input['supervisor_contact'] ?? ''));
        if ($contact !== '' && mb_strlen($contact) > 150) {
            $errors['supervisor_contact'][] = 'Supervisor contact may not exceed 150 characters.';
        }

        $skillIds = $input['skill_ids'] ?? [];
        if (!is_array($skillIds)) {
            $errors['skill_ids'][] = 'Skills used must be a list.';
        } elseif ($allowedSkillIds !== null) {
            foreach ($skillIds as $sid) {
                if (!in_array((int) $sid, $allowedSkillIds, true)) {
                    $errors['skill_ids'][] = 'One or more skills are invalid.';
                    break;
                }
            }
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

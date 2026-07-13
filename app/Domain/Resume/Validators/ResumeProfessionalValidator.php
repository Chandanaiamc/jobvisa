<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Validators;

/**
 * Domain validation for resume professional summary section.
 */
final class ResumeProfessionalValidator
{
    public const CURRENCIES = ['LKR', 'USD', 'AED', 'QAR', 'SAR', 'EUR', 'GBP'];

    public const NOTICE_PERIODS = [
        'immediate',
        '1_week',
        '2_weeks',
        '1_month',
        '2_months',
        '3_months',
        'negotiable',
    ];

    public const EMPLOYMENT_STATUSES = [
        'employed',
        'unemployed',
        'freelance',
        'contract',
        'student',
        'retired',
    ];

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, list<string>>
     */
    public function validate(array $input): array
    {
        $errors = [];

        $headline = trim((string) ($input['headline'] ?? ''));
        if ($headline === '') {
            $errors['headline'][] = 'Professional headline is required.';
        } elseif (mb_strlen($headline) > 255) {
            $errors['headline'][] = 'Headline may not exceed 255 characters.';
        }

        $summary = trim((string) ($input['summary'] ?? ''));
        if ($summary === '') {
            $errors['summary'][] = 'Professional summary is required.';
        } elseif (mb_strlen($summary) < 40) {
            $errors['summary'][] = 'Summary should be at least 40 characters.';
        } elseif (mb_strlen($summary) > 5000) {
            $errors['summary'][] = 'Summary may not exceed 5000 characters.';
        }

        $objective = trim((string) ($input['career_objective'] ?? ''));
        if ($objective !== '' && mb_strlen($objective) > 2000) {
            $errors['career_objective'][] = 'Career objective may not exceed 2000 characters.';
        }

        $years = $input['years_of_experience'] ?? null;
        if ($years !== null && $years !== '') {
            if (!is_numeric($years) || (float) $years < 0 || (float) $years > 60) {
                $errors['years_of_experience'][] = 'Years of experience must be between 0 and 60.';
            }
        }

        foreach (['current_job_title' => 150, 'current_company' => 200, 'industry' => 150] as $field => $max) {
            $value = trim((string) ($input[$field] ?? ''));
            if ($value !== '' && mb_strlen($value) > $max) {
                $errors[$field][] = str_replace('_', ' ', ucfirst($field)) . " may not exceed {$max} characters.";
            }
        }

        $expected = $input['expected_salary'] ?? null;
        if ($expected === null || $expected === '') {
            $errors['expected_salary'][] = 'Expected salary is required.';
        } elseif (!is_numeric($expected) || (float) $expected < 0) {
            $errors['expected_salary'][] = 'Salary must be a non-negative number.';
        }

        $current = $input['current_salary'] ?? null;
        if ($current !== null && $current !== '') {
            if (!is_numeric($current) || (float) $current < 0) {
                $errors['current_salary'][] = 'Salary must be a non-negative number.';
            }
        }

        $currency = strtoupper(trim((string) ($input['preferred_currency'] ?? '')));
        if ($currency !== '' && !in_array($currency, self::CURRENCIES, true)) {
            $errors['preferred_currency'][] = 'Preferred currency is not allowed.';
        }

        $notice = trim((string) ($input['notice_period'] ?? ''));
        if ($notice !== '' && !in_array($notice, self::NOTICE_PERIODS, true)) {
            $errors['notice_period'][] = 'Notice period is invalid.';
        }

        $status = trim((string) ($input['employment_status'] ?? ''));
        if ($status === '') {
            $errors['employment_status'][] = 'Employment status is required.';
        } elseif (!in_array($status, self::EMPLOYMENT_STATUSES, true)) {
            $errors['employment_status'][] = 'Employment status is invalid.';
        }

        return $errors;
    }
}

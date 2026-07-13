<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Validators;

/**
 * Field-level validation for resume personal information.
 */
final class ResumePersonalValidator
{
    public const GENDERS = ['male', 'female', 'other'];
    public const MARITAL = ['single', 'married', 'divorced', 'widowed'];
    public const VISA = ['none', 'tourist', 'work', 'resident', 'other'];
    public const LICENCE = ['none', 'light', 'heavy', 'motorcycle', 'international'];
    public const CURRENCIES = ['LKR', 'USD', 'AED', 'QAR', 'SAR', 'EUR', 'GBP'];

    /**
     * @param  array<string, mixed>  $input
     * @param  list<int>  $validCountryIds
     * @return array<string, list<string>>
     */
    public function validate(array $input, array $validCountryIds): array
    {
        $errors = [];

        $first = trim((string) ($input['first_name'] ?? ''));
        $last = trim((string) ($input['last_name'] ?? ''));

        if ($first === '') {
            $errors['first_name'][] = 'First name is required.';
        } elseif (mb_strlen($first) > 80) {
            $errors['first_name'][] = 'First name may not exceed 80 characters.';
        }

        if ($last === '') {
            $errors['last_name'][] = 'Last name is required.';
        } elseif (mb_strlen($last) > 80) {
            $errors['last_name'][] = 'Last name may not exceed 80 characters.';
        }

        if (array_key_exists('email', $input) && trim((string) $input['email']) !== '') {
            // Email is read-only; ignore edits silently — controller must not pass editable email.
        }

        $this->optionalMax($errors, $input, 'headline', 255);
        $this->optionalMax($errors, $input, 'nic_number', 64);
        $this->optionalMax($errors, $input, 'passport_number', 64);
        $this->optionalMax($errors, $input, 'phone', 32);
        $this->optionalMax($errors, $input, 'whatsapp', 32);
        $this->optionalMax($errors, $input, 'address', 1000);

        $this->optionalDate($errors, $input, 'date_of_birth', false);
        $this->optionalDate($errors, $input, 'passport_expiry', false);

        $dob = trim((string) ($input['date_of_birth'] ?? ''));
        $expiry = trim((string) ($input['passport_expiry'] ?? ''));

        if ($dob !== '' && $expiry !== '' && strtotime($expiry) !== false && strtotime($dob) !== false) {
            if (strtotime($expiry) < strtotime($dob)) {
                $errors['passport_expiry'][] = 'Passport expiry cannot be before date of birth.';
            }
        }

        if ($expiry !== '' && strtotime($expiry) !== false && strtotime($expiry) < strtotime('-50 years')) {
            $errors['passport_expiry'][] = 'Passport expiry date looks invalid.';
        }

        $this->optionalIn($errors, $input, 'gender', self::GENDERS);
        $this->optionalIn($errors, $input, 'marital_status', self::MARITAL);
        $this->optionalIn($errors, $input, 'visa_status', self::VISA);
        $this->optionalIn($errors, $input, 'driving_licence_status', self::LICENCE);

        $currency = strtoupper(trim((string) ($input['salary_currency'] ?? '')));

        if ($currency !== '' && !in_array($currency, self::CURRENCIES, true)) {
            $errors['salary_currency'][] = 'Salary currency is not allowed.';
        }

        $salary = $input['expected_salary'] ?? null;

        if ($salary !== null && $salary !== '') {
            if (!is_numeric($salary) || (float) $salary < 0) {
                $errors['expected_salary'][] = 'Expected salary must be a non-negative number.';
            }
        }

        foreach (['nationality_country_id', 'current_country_id'] as $field) {
            $id = $input[$field] ?? null;

            if ($id === null || $id === '') {
                continue;
            }

            if (!in_array((int) $id, $validCountryIds, true)) {
                $errors[$field][] = 'Selected country is invalid.';
            }
        }

        $preferred = $input['preferred_country_ids'] ?? [];

        if (!is_array($preferred)) {
            $preferred = [];
        }

        foreach ($preferred as $countryId) {
            if (!in_array((int) $countryId, $validCountryIds, true)) {
                $errors['preferred_country_ids'][] = 'One or more preferred countries are invalid.';
                break;
            }
        }

        return $errors;
    }

    /**
     * @param  array<string, list<string>>  $errors
     * @param  array<string, mixed>  $input
     */
    private function optionalMax(array &$errors, array $input, string $field, int $max): void
    {
        $value = trim((string) ($input[$field] ?? ''));

        if ($value !== '' && mb_strlen($value) > $max) {
            $errors[$field][] = 'The ' . str_replace('_', ' ', $field) . ' may not exceed ' . $max . ' characters.';
        }
    }

    /**
     * @param  array<string, list<string>>  $errors
     * @param  array<string, mixed>  $input
     * @param  list<string>  $allowed
     */
    private function optionalIn(array &$errors, array $input, string $field, array $allowed): void
    {
        $value = trim((string) ($input[$field] ?? ''));

        if ($value !== '' && !in_array($value, $allowed, true)) {
            $errors[$field][] = 'The selected ' . str_replace('_', ' ', $field) . ' is invalid.';
        }
    }

    /**
     * @param  array<string, list<string>>  $errors
     * @param  array<string, mixed>  $input
     */
    private function optionalDate(array &$errors, array $input, string $field, bool $required): void
    {
        $value = trim((string) ($input[$field] ?? ''));

        if ($value === '') {
            if ($required) {
                $errors[$field][] = 'This date is required.';
            }

            return;
        }

        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $value);

        if ($dt === false || $dt->format('Y-m-d') !== $value) {
            $errors[$field][] = 'Please provide a valid date (YYYY-MM-DD).';
        }
    }
}

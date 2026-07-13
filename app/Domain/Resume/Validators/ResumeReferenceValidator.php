<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Validators;

/**
 * Domain validation for resume professional references (Sprint 2E.4).
 */
final class ResumeReferenceValidator
{
    public const VISIBILITIES = ['public', 'employers', 'private'];

    public const STATUSES = ['active', 'hidden'];

    public const SORTS = ['sort_order', 'newest', 'oldest', 'name', 'featured'];

    /** Canonical relationship values (stored in `relationship`). */
    public const RELATIONSHIPS = [
        'former_manager',
        'current_manager',
        'colleague',
        'client',
        'mentor',
        'professor',
        'supervisor',
        'hr_contact',
        'other',
    ];

    public const RELATIONSHIP_LABELS = [
        'former_manager' => 'Former manager',
        'current_manager' => 'Current manager',
        'colleague' => 'Colleague',
        'client' => 'Client',
        'mentor' => 'Mentor',
        'professor' => 'Professor / academic',
        'supervisor' => 'Supervisor',
        'hr_contact' => 'HR contact',
        'other' => 'Other',
    ];

    /**
     * @param  array<string, mixed>  $input
     * @param  callable(int): bool|null  $projectOwned
     * @param  callable(int): bool|null  $countryExists
     * @param  callable(int, int): bool|null  $cityBelongsToCountry
     * @param  callable(string, ?string): bool|null  $isDuplicate
     * @return array<string, list<string>>
     */
    public function validate(
        array $input,
        ?callable $projectOwned = null,
        ?callable $countryExists = null,
        ?callable $cityBelongsToCountry = null,
        ?callable $isDuplicate = null
    ): array {
        $errors = [];
        $input = $this->normalizeAliases($input);

        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            $errors['name'][] = 'Reference name is required.';
        } elseif (mb_strlen($name) > 200) {
            $errors['name'][] = 'Reference name may not exceed 200 characters.';
        }

        foreach (
            [
                'designation' => 200,
                'company' => 200,
            ] as $field => $max
        ) {
            $value = trim((string) ($input[$field] ?? ''));
            if ($value !== '' && mb_strlen($value) > $max) {
                $label = $field === 'designation' ? 'Position' : ucwords(str_replace('_', ' ', $field));
                $errors[$field][] = "{$label} may not exceed {$max} characters.";
            }
        }

        $relationship = trim((string) ($input['relationship'] ?? ''));
        if ($relationship !== '' && mb_strlen($relationship) > 120) {
            $errors['relationship'][] = 'Reference relationship may not exceed 120 characters.';
        }

        $email = trim((string) ($input['email'] ?? ''));
        if ($email !== '') {
            if (mb_strlen($email) > 255) {
                $errors['email'][] = 'Email may not exceed 255 characters.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'][] = 'Email is not valid.';
            }
        }

        $phone = trim((string) ($input['phone'] ?? ''));
        if ($phone !== '' && mb_strlen($phone) > 40) {
            $errors['phone'][] = 'Phone may not exceed 40 characters.';
        }

        $yearsRaw = trim((string) ($input['years_known'] ?? ''));
        if ($yearsRaw !== '') {
            if (!is_numeric($yearsRaw)) {
                $errors['years_known'][] = 'Years known must be a number.';
            } else {
                $years = (float) $yearsRaw;
                if ($years < 0 || $years > 99.9) {
                    $errors['years_known'][] = 'Years known must be between 0 and 99.9.';
                }
            }
        }

        $notes = trim((string) ($input['notes'] ?? ''));
        if ($notes !== '' && mb_strlen($notes) > 15000) {
            $errors['notes'][] = 'Notes may not exceed 15,000 characters.';
        }

        $projectId = trim((string) ($input['project_id'] ?? ''));
        if ($projectId !== '') {
            if (!ctype_digit($projectId) || (int) $projectId < 1) {
                $errors['project_id'][] = 'Associated project is invalid.';
            } elseif ($projectOwned !== null && !$projectOwned((int) $projectId)) {
                $errors['project_id'][] = 'Associated project must belong to this resume.';
            }
        }

        $countryId = trim((string) ($input['country_id'] ?? ''));
        $cityId = trim((string) ($input['city_id'] ?? ''));
        $countryInt = null;
        if ($countryId !== '') {
            if (!ctype_digit($countryId) || (int) $countryId < 1) {
                $errors['country_id'][] = 'Country is invalid.';
            } else {
                $countryInt = (int) $countryId;
                if ($countryExists !== null && !$countryExists($countryInt)) {
                    $errors['country_id'][] = 'Country is invalid.';
                }
            }
        }
        if ($cityId !== '') {
            if (!ctype_digit($cityId) || (int) $cityId < 1) {
                $errors['city_id'][] = 'City is invalid.';
            } elseif ($countryInt === null) {
                $errors['city_id'][] = 'Select a country before choosing a city.';
            } elseif ($cityBelongsToCountry !== null && !$cityBelongsToCountry((int) $cityId, $countryInt)) {
                $errors['city_id'][] = 'City must belong to the selected country.';
            }
        }

        $visibility = trim((string) ($input['visibility'] ?? 'private'));
        if ($visibility === '' || !in_array($visibility, self::VISIBILITIES, true)) {
            $errors['visibility'][] = 'Visibility is invalid.';
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

        if ($name !== '' && $isDuplicate !== null) {
            $company = trim((string) ($input['company'] ?? ''));
            if ($isDuplicate($name, $company !== '' ? $company : null)) {
                $errors['name'][] = 'A reference with the same name and company already exists on this resume.';
            }
        }

        return $errors;
    }

    /**
     * Map Sprint 2E.4 "position" alias onto stored `designation` column (no schema break).
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function normalizeAliases(array $input): array
    {
        $position = trim((string) ($input['position'] ?? ''));
        $designation = trim((string) ($input['designation'] ?? ''));
        if ($designation === '' && $position !== '') {
            $input['designation'] = $position;
        }

        return $input;
    }
}

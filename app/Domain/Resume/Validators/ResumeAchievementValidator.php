<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Validators;

/**
 * Domain validation for resume awards & achievements.
 */
final class ResumeAchievementValidator
{
    public const STATUSES = ['active', 'archived'];

    public const VISIBILITIES = ['public', 'private'];

    public const TYPES = [
        'award',
        'recognition',
        'scholarship',
        'competition',
        'publication',
        'honor',
        'other',
    ];

    public const AWARD_LEVELS = [
        'local',
        'district',
        'provincial',
        'national',
        'regional',
        'international',
    ];

    /**
     * @param  array<string, mixed>  $input
     * @param  callable(int): bool|null  $projectOwned
     * @param  callable(int): bool|null  $countryExists
     * @param  callable(int, int): bool|null  $cityBelongsToCountry
     * @return array<string, list<string>>
     */
    public function validate(
        array $input,
        ?callable $projectOwned = null,
        ?callable $countryExists = null,
        ?callable $cityBelongsToCountry = null
    ): array {
        $errors = [];

        $title = trim((string) ($input['title'] ?? ''));
        if ($title === '') {
            $errors['title'][] = 'Achievement title is required.';
        } elseif (mb_strlen($title) > 200) {
            $errors['title'][] = 'Achievement title may not exceed 200 characters.';
        }

        $issuer = trim((string) ($input['issuer'] ?? ''));
        if ($issuer !== '' && mb_strlen($issuer) > 200) {
            $errors['issuer'][] = 'Issuer may not exceed 200 characters.';
        }

        foreach (['description' => 10000, 'remarks' => 5000] as $field => $max) {
            $value = trim((string) ($input[$field] ?? ''));
            if ($value !== '' && mb_strlen($value) > $max) {
                $errors[$field][] = ucfirst($field) . " may not exceed {$max} characters.";
            }
        }

        $type = trim((string) ($input['achievement_type'] ?? ''));
        if ($type !== '' && !in_array($type, self::TYPES, true)) {
            $errors['achievement_type'][] = 'Achievement type is invalid.';
        }

        $level = trim((string) ($input['award_level'] ?? ''));
        if ($level !== '' && !in_array($level, self::AWARD_LEVELS, true)) {
            $errors['award_level'][] = 'Award level is invalid.';
        }

        $rank = trim((string) ($input['rank_or_placement'] ?? ''));
        if ($rank !== '' && mb_strlen($rank) > 120) {
            $errors['rank_or_placement'][] = 'Rank / placement may not exceed 120 characters.';
        }

        $date = trim((string) ($input['achievement_date'] ?? ''));
        if ($date !== '' && !$this->isDate($date)) {
            $errors['achievement_date'][] = 'Achievement date is invalid.';
        }

        $url = trim((string) ($input['credential_url'] ?? ''));
        if ($url !== '') {
            if (mb_strlen($url) > 500) {
                $errors['credential_url'][] = 'Credential URL may not exceed 500 characters.';
            } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
                $errors['credential_url'][] = 'Credential URL is not valid.';
            }
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

        $status = trim((string) ($input['status'] ?? 'active'));
        if ($status !== '' && !in_array($status, self::STATUSES, true)) {
            $errors['status'][] = 'Status is invalid.';
        }

        $visibility = trim((string) ($input['visibility'] ?? 'public'));
        if ($visibility === '' || !in_array($visibility, self::VISIBILITIES, true)) {
            $errors['visibility'][] = 'Visibility must be public or private.';
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

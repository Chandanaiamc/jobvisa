<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Validators;

/**
 * Domain validation for resume professional portfolio items.
 */
final class ResumePortfolioValidator
{
    public const CATEGORIES = [
        'web',
        'mobile',
        'ui_ux',
        'graphic_design',
        'branding',
        'photography',
        'video',
        'illustration',
        'writing',
        'research',
        'open_source',
        'product',
        'other',
    ];

    public const VISIBILITIES = ['public', 'employers', 'private'];

    public const STATUSES = ['active', 'hidden'];

    public const SORTS = ['sort_order', 'newest', 'oldest', 'title', 'featured'];

    private const URL_FIELDS = [
        'portfolio_url',
        'github_url',
        'behance_url',
        'dribbble_url',
        'figma_url',
        'youtube_url',
        'google_drive_url',
    ];

    /**
     * @param  array<string, mixed>  $input
     * @param  callable(int): bool|null  $projectOwned
     * @param  callable(int): bool|null  $countryExists
     * @param  callable(int, int): bool|null  $cityBelongsToCountry
     * @param  callable(string, string): bool|null  $isDuplicate
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

        $title = trim((string) ($input['title'] ?? ''));
        if ($title === '') {
            $errors['title'][] = 'Portfolio title is required.';
        } elseif (mb_strlen($title) > 200) {
            $errors['title'][] = 'Portfolio title may not exceed 200 characters.';
        }

        $category = trim((string) ($input['category'] ?? ''));
        if ($category === '') {
            $errors['category'][] = 'Category is required.';
        } elseif (!in_array($category, self::CATEGORIES, true)) {
            $errors['category'][] = 'Category is invalid.';
        }

        $description = trim((string) ($input['description'] ?? ''));
        if ($description !== '' && mb_strlen($description) > 15000) {
            $errors['description'][] = 'Description may not exceed 15,000 characters.';
        }

        foreach (self::URL_FIELDS as $field) {
            $url = trim((string) ($input[$field] ?? ''));
            if ($url === '') {
                continue;
            }
            $label = ucwords(str_replace('_', ' ', $field));
            if (mb_strlen($url) > 500) {
                $errors[$field][] = "{$label} may not exceed 500 characters.";
            } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
                $errors[$field][] = "{$label} is not valid.";
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

        $visibility = trim((string) ($input['visibility'] ?? 'public'));
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

        if ($title !== '' && $category !== '' && $isDuplicate !== null) {
            if ($isDuplicate($title, $category)) {
                $errors['title'][] = 'A portfolio item with the same title and category already exists on this resume.';
            }
        }

        return $errors;
    }
}

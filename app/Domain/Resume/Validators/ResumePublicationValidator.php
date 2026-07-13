<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Validators;

/**
 * Domain validation for resume publications & research.
 */
final class ResumePublicationValidator
{
    public const TYPES = [
        'book',
        'book_chapter',
        'research_paper',
        'journal_article',
        'conference_paper',
        'thesis',
        'dissertation',
        'white_paper',
        'technical_report',
        'patent',
        'magazine_article',
        'newspaper_article',
        'blog_post',
        'case_study',
        'working_paper',
        'other',
    ];

    public const VISIBILITIES = ['public', 'employers', 'private'];

    public const STATUSES = ['active', 'hidden'];

    public const SORTS = ['sort_order', 'newest', 'oldest', 'title', 'year', 'featured'];

    /**
     * @param  array<string, mixed>  $input
     * @param  callable(int): bool|null  $projectOwned
     * @param  callable(int): bool|null  $countryExists
     * @param  callable(int, int): bool|null  $cityBelongsToCountry
     * @param  callable(string, ?string, ?int): bool|null  $isDuplicate
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
            $errors['title'][] = 'Publication title is required.';
        } elseif (mb_strlen($title) > 300) {
            $errors['title'][] = 'Publication title may not exceed 300 characters.';
        }

        $type = trim((string) ($input['publication_type'] ?? ''));
        if ($type === '') {
            $errors['publication_type'][] = 'Publication type is required.';
        } elseif (!in_array($type, self::TYPES, true)) {
            $errors['publication_type'][] = 'Publication type is invalid.';
        }

        foreach (
            [
                'publisher' => 255,
                'user_contribution' => 200,
                'volume' => 64,
                'issue' => 64,
                'page_range' => 64,
                'doi' => 200,
                'isbn' => 32,
                'issn' => 32,
                'patent_number' => 120,
                'conference_name' => 255,
                'keywords' => 1000,
            ] as $field => $max
        ) {
            $value = trim((string) ($input[$field] ?? ''));
            if ($value !== '' && mb_strlen($value) > $max) {
                $label = ucwords(str_replace('_', ' ', $field));
                $errors[$field][] = "{$label} may not exceed {$max} characters.";
            }
        }

        $authors = trim((string) ($input['authors'] ?? ''));
        if ($authors !== '' && mb_strlen($authors) > 5000) {
            $errors['authors'][] = 'Authors may not exceed 5,000 characters.';
        }

        $summary = trim((string) ($input['abstract_summary'] ?? ''));
        if ($summary !== '' && mb_strlen($summary) > 15000) {
            $errors['abstract_summary'][] = 'Abstract may not exceed 15,000 characters.';
        }

        $url = trim((string) ($input['publication_url'] ?? ''));
        if ($url !== '') {
            if (mb_strlen($url) > 500) {
                $errors['publication_url'][] = 'Publication URL may not exceed 500 characters.';
            } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
                $errors['publication_url'][] = 'Publication URL is not valid.';
            }
        }

        $doi = trim((string) ($input['doi'] ?? ''));
        if ($doi !== '' && !preg_match('/^10\.\d{4,9}\/[-._;()\/:A-Z0-9]+$/i', $doi)) {
            $errors['doi'][] = 'DOI format is invalid.';
        }

        $isbn = preg_replace('/[\s-]/', '', trim((string) ($input['isbn'] ?? ''))) ?? '';
        if ($isbn !== '' && !preg_match('/^(?:\d{9}[\dXx]|\d{13})$/', $isbn)) {
            $errors['isbn'][] = 'ISBN must be 10 or 13 digits.';
        }

        $issn = preg_replace('/[\s-]/', '', trim((string) ($input['issn'] ?? ''))) ?? '';
        if ($issn !== '' && !preg_match('/^\d{7}[\dXx]$/', $issn)) {
            $errors['issn'][] = 'ISSN format is invalid.';
        }

        $date = trim((string) ($input['publication_date'] ?? ''));
        if ($date !== '' && !$this->isDate($date)) {
            $errors['publication_date'][] = 'Publication date is invalid.';
        }

        $yearRaw = trim((string) ($input['publication_year'] ?? ''));
        $year = null;
        if ($yearRaw !== '') {
            if (!ctype_digit($yearRaw)) {
                $errors['publication_year'][] = 'Publication year is invalid.';
            } else {
                $year = (int) $yearRaw;
                $current = (int) date('Y') + 1;
                if ($year < 1900 || $year > $current) {
                    $errors['publication_year'][] = 'Publication year must be between 1900 and ' . $current . '.';
                }
            }
        } elseif ($date !== '' && $this->isDate($date)) {
            $year = (int) substr($date, 0, 4);
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

        if ($title !== '' && $isDuplicate !== null) {
            $publisher = trim((string) ($input['publisher'] ?? ''));
            if ($isDuplicate($title, $publisher !== '' ? $publisher : null, $year)) {
                $errors['title'][] = 'A similar publication (same title, publisher, and year) already exists on this resume.';
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

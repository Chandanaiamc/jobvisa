<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Validators;

/**
 * Domain validation for resume projects & portfolio.
 */
final class ResumeProjectValidator
{
    public const STATUSES = ['active', 'archived'];

    public const VISIBILITIES = ['public', 'private'];

    public const PROJECT_TYPES = [
        'client',
        'personal',
        'open_source',
        'academic',
        'freelance',
        'internal',
        'volunteer',
        'other',
    ];

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, list<string>>
     */
    public function validate(array $input): array
    {
        $errors = [];

        $title = trim((string) ($input['title'] ?? ''));
        if ($title === '') {
            $errors['title'][] = 'Project title is required.';
        } elseif (mb_strlen($title) > 200) {
            $errors['title'][] = 'Project title may not exceed 200 characters.';
        }

        foreach (
            [
                'client_name' => 200,
                'organization' => 200,
                'role' => 150,
                'industry' => 150,
                'location' => 200,
            ] as $field => $max
        ) {
            $value = trim((string) ($input[$field] ?? ''));
            if ($value !== '' && mb_strlen($value) > $max) {
                $label = ucwords(str_replace('_', ' ', $field));
                $errors[$field][] = "{$label} may not exceed {$max} characters.";
            }
        }

        foreach (['description', 'achievements', 'responsibilities'] as $field) {
            $value = trim((string) ($input[$field] ?? ''));
            if ($value !== '' && mb_strlen($value) > 10000) {
                $errors[$field][] = ucfirst($field) . ' may not exceed 10,000 characters.';
            }
        }

        $techRaw = $input['technologies'] ?? '';
        if (is_array($techRaw)) {
            $techCount = count(array_filter(array_map('trim', array_map('strval', $techRaw))));
        } else {
            $parts = preg_split('/[,;|]+/', (string) $techRaw) ?: [];
            $techCount = count(array_filter(array_map('trim', $parts)));
        }
        if ($techCount > 40) {
            $errors['technologies'][] = 'You may list at most 40 technologies.';
        }
        if (is_string($techRaw) && mb_strlen($techRaw) > 2000) {
            $errors['technologies'][] = 'Technologies may not exceed 2000 characters.';
        }

        foreach (['project_url', 'github_url', 'portfolio_url', 'video_demo_url'] as $field) {
            $url = trim((string) ($input[$field] ?? ''));
            if ($url === '') {
                continue;
            }
            if (mb_strlen($url) > 500) {
                $errors[$field][] = 'URL may not exceed 500 characters.';
            } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
                $errors[$field][] = 'URL is not valid.';
            }
        }

        $start = trim((string) ($input['start_date'] ?? ''));
        if ($start !== '' && !$this->isDate($start)) {
            $errors['start_date'][] = 'Start date is invalid.';
        }

        $current = !empty($input['currently_working']);
        $end = trim((string) ($input['end_date'] ?? ''));
        if (!$current) {
            if ($end !== '' && !$this->isDate($end)) {
                $errors['end_date'][] = 'End date is invalid.';
            }
        } elseif ($end !== '' && !$this->isDate($end)) {
            $errors['end_date'][] = 'End date is invalid.';
        }

        if ($start !== '' && $end !== '' && $this->isDate($start) && $this->isDate($end) && $end < $start) {
            $errors['end_date'][] = 'End date cannot be before start date.';
        }

        if (isset($input['team_size']) && $input['team_size'] !== '') {
            if (!is_numeric($input['team_size']) || (int) $input['team_size'] < 1 || (int) $input['team_size'] > 10000) {
                $errors['team_size'][] = 'Team size must be between 1 and 10000.';
            }
        }

        $type = trim((string) ($input['project_type'] ?? ''));
        if ($type !== '' && !in_array($type, self::PROJECT_TYPES, true)) {
            $errors['project_type'][] = 'Project category is invalid.';
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

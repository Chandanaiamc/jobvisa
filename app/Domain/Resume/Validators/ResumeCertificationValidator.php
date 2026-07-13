<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Validators;

/**
 * Domain validation for resume certifications & licences.
 */
final class ResumeCertificationValidator
{
    public const STATUSES = ['active', 'archived'];

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, list<string>>
     */
    public function validate(array $input): array
    {
        $errors = [];

        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            $errors['name'][] = 'Certification name is required.';
        } elseif (mb_strlen($name) > 200) {
            $errors['name'][] = 'Certification name may not exceed 200 characters.';
        }

        $org = trim((string) ($input['issuing_organization'] ?? ''));
        if ($org === '') {
            $errors['issuing_organization'][] = 'Issuing organization is required.';
        } elseif (mb_strlen($org) > 200) {
            $errors['issuing_organization'][] = 'Issuing organization may not exceed 200 characters.';
        }

        foreach (['credential_id' => 120, 'license_number' => 120] as $field => $max) {
            $value = trim((string) ($input[$field] ?? ''));
            if ($value !== '' && mb_strlen($value) > $max) {
                $errors[$field][] = str_replace('_', ' ', ucfirst($field)) . " may not exceed {$max} characters.";
            }
        }

        foreach (['credential_url', 'verification_url'] as $field) {
            $url = trim((string) ($input[$field] ?? ''));
            if ($url !== '') {
                if (mb_strlen($url) > 500) {
                    $errors[$field][] = 'URL may not exceed 500 characters.';
                } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
                    $errors[$field][] = 'URL is not valid.';
                }
            }
        }

        $issue = trim((string) ($input['issue_date'] ?? ''));
        if ($issue === '') {
            $errors['issue_date'][] = 'Issue date is required.';
        } elseif (!$this->isDate($issue)) {
            $errors['issue_date'][] = 'Issue date is invalid.';
        }

        $noExpire = !empty($input['does_not_expire']);
        $expiry = trim((string) ($input['expiry_date'] ?? ''));
        if (!$noExpire) {
            if ($expiry === '') {
                $errors['expiry_date'][] = 'Expiry date is required unless it does not expire.';
            } elseif (!$this->isDate($expiry)) {
                $errors['expiry_date'][] = 'Expiry date is invalid.';
            }
        } elseif ($expiry !== '' && !$this->isDate($expiry)) {
            $errors['expiry_date'][] = 'Expiry date is invalid.';
        }

        if ($issue !== '' && $expiry !== '' && $this->isDate($issue) && $this->isDate($expiry) && $expiry < $issue) {
            $errors['expiry_date'][] = 'Expiry date cannot be before issue date.';
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

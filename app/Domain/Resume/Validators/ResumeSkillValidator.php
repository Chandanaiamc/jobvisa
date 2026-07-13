<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Validators;

/**
 * Domain validation for resume skills.
 */
final class ResumeSkillValidator
{
    public const LEVELS = ['beginner', 'intermediate', 'advanced', 'expert'];

    public const STATUSES = ['active', 'archived'];

    /**
     * @param  array<string, mixed>  $input
     * @param  callable(int): bool|null  $skillExists
     * @return array<string, list<string>>
     */
    public function validate(array $input, ?callable $skillExists = null, bool $requireSkillId = true): array
    {
        $errors = [];

        $skillId = (int) ($input['skill_id'] ?? 0);
        if ($requireSkillId) {
            if ($skillId < 1) {
                $errors['skill_id'][] = 'Select a skill from the catalogue.';
            } elseif ($skillExists !== null && !$skillExists($skillId)) {
                $errors['skill_id'][] = 'Skill is invalid or inactive.';
            }
        }

        $level = strtolower(trim((string) ($input['level'] ?? '')));
        if ($level === '') {
            $errors['level'][] = 'Skill level is required.';
        } elseif (!in_array($level, self::LEVELS, true)) {
            $errors['level'][] = 'Skill level must be Beginner, Intermediate, Advanced, or Expert.';
        }

        $years = $input['years_experience'] ?? null;
        if ($years !== null && $years !== '') {
            if (!is_numeric($years) || (float) $years < 0 || (float) $years > 60) {
                $errors['years_experience'][] = 'Years of experience must be between 0 and 60.';
            }
        }

        $lastUsed = $input['last_used_year'] ?? null;
        if ($lastUsed !== null && $lastUsed !== '') {
            $year = (int) $lastUsed;
            $max = (int) date('Y') + 1;
            if ($year < 1950 || $year > $max) {
                $errors['last_used_year'][] = 'Last used year is out of range.';
            }
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
}

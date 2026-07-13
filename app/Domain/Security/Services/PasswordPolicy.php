<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Security\Services;

/**
 * Configurable password strength policy (defaults = existing min length 8).
 */
final class PasswordPolicy
{
    /**
     * @return list<string> error messages (empty = ok)
     */
    public function validate(string $password): array
    {
        $errors = [];
        $min = (int) config('security.password_min_length', 8);

        if (mb_strlen($password) < $min) {
            $errors[] = 'Password must be at least ' . $min . ' characters.';
        }

        if ((bool) config('security.password_require_mixed', false)) {
            if (!preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password)) {
                $errors[] = 'Password must include both uppercase and lowercase letters.';
            }
        }

        if ((bool) config('security.password_require_number', false) && !preg_match('/\d/', $password)) {
            $errors[] = 'Password must include at least one number.';
        }

        if ((bool) config('security.password_require_symbol', false) && !preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = 'Password must include at least one symbol.';
        }

        return $errors;
    }

    public function passes(string $password): bool
    {
        return $this->validate($password) === [];
    }
}

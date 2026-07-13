<?php

declare(strict_types=1);

namespace JobVisa\App\Auth;

use JobVisa\App\Security\SecurityHelper;

/**
 * Remember-me token foundation (issue, hash, store, validate, clear).
 *
 * Cookie issuance is deferred to a future HTTP layer — this service only
 * manages the token value and `users.remember_token` hash storage.
 */
final class RememberMeService
{
    public function __construct(
        private readonly UserRepository $users
    ) {
    }

    /**
     * Create a new remember-me token for a user.
     *
     * @return array{plain: string, hash: string}
     */
    public function issue(int $userId): array
    {
        $plain = SecurityHelper::randomToken(32);
        $hash = $this->hash($plain);
        $this->users->updateRememberToken($userId, $hash);

        return [
            'plain' => $plain,
            'hash' => $hash,
        ];
    }

    public function hash(string $plainToken): string
    {
        if ((bool) config('security.remember_pepper_enabled', false)) {
            $pepper = (string) env('APP_KEY', '');
            if ($pepper !== '') {
                return hash_hmac('sha256', $plainToken, $pepper);
            }
        }

        return hash('sha256', $plainToken);
    }

    /**
     * Validate a raw token against the stored hash for a user.
     */
    public function validate(int $userId, string $plainToken): bool
    {
        if ($plainToken === '' || $userId < 1) {
            return false;
        }

        $user = $this->users->findActiveById($userId);

        if ($user === null) {
            return false;
        }

        $stored = $user['remember_token'] ?? null;

        if (!is_string($stored) || $stored === '') {
            return false;
        }

        return hash_equals($stored, $this->hash($plainToken));
    }

    /**
     * Clear the stored remember-me hash for a user.
     */
    public function clear(int $userId): void
    {
        if ($userId < 1) {
            return;
        }

        $this->users->updateRememberToken($userId, null);
    }
}

<?php

declare(strict_types=1);

namespace JobVisa\App\Auth;

/**
 * Password hashing with Argon2id preferred and bcrypt fallback.
 */
final class PasswordHasher
{
    /**
     * Hash a plain-text password for storage.
     */
    public function hash(string $password): string
    {
        $algo = $this->algorithm();
        $hash = password_hash($password, $algo, $this->options($algo));

        if ($hash === false) {
            throw new \RuntimeException('Unable to hash password.');
        }

        return $hash;
    }

    /**
     * Verify a plain-text password against a stored hash.
     */
    public function verify(string $password, string $hash): bool
    {
        if ($password === '' || $hash === '') {
            return false;
        }

        return password_verify($password, $hash);
    }

    /**
     * Whether the stored hash should be upgraded to the current algorithm/options.
     */
    public function needsRehash(string $hash): bool
    {
        if ($hash === '') {
            return true;
        }

        $algo = $this->algorithm();

        return password_needs_rehash($hash, $algo, $this->options($algo));
    }

    /**
     * @return array<string, mixed>
     */
    private function options(string|int $algo): array
    {
        if ($algo === PASSWORD_ARGON2ID || $algo === 'argon2id') {
            return [
                'memory_cost' => 65536,
                'time_cost' => 4,
                'threads' => 1,
            ];
        }

        return [
            'cost' => 12,
        ];
    }

    private function algorithm(): string|int
    {
        $configured = strtolower((string) config('security.password_algo', 'argon2id'));
        if ($configured === 'bcrypt') {
            return PASSWORD_BCRYPT;
        }

        if (defined('PASSWORD_ARGON2ID') && is_int(PASSWORD_ARGON2ID)) {
            return PASSWORD_ARGON2ID;
        }

        return PASSWORD_BCRYPT;
    }
}

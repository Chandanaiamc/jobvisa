<?php

declare(strict_types=1);

namespace JobVisa\App\Auth;

use JobVisa\App\Logging\Logger;

/**
 * Core authentication orchestrator.
 */
final class AuthManager
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordHasher $hasher,
        private readonly SessionManager $session,
        private readonly LoginAttemptService $loginAttempts,
        private readonly RememberMeService $rememberMe
    ) {
    }

    /**
     * Whether login should be blocked due to recent failures.
     */
    public function isThrottled(?string $email = null): bool
    {
        $window = (int) config('auth.login_attempts.window_minutes', 15);
        $max = (int) config('auth.login_attempts.max_failures', 5);

        if ($max < 1) {
            return false;
        }

        $byIp = $this->loginAttempts->countRecentFailuresByIp($window);

        if ($byIp >= $max) {
            return true;
        }

        if ($email !== null && trim($email) !== '') {
            return $this->loginAttempts->countRecentFailuresByEmail($email, $window) >= $max;
        }

        return false;
    }

    /**
     * Attempt credential authentication.
     *
     * @return array{success: bool, user_id: ?int, remember: ?array{plain: string, hash: string}, throttled?: bool, message?: string}
     */
    public function attempt(string $email, string $password, bool $remember = false): array
    {
        $email = strtolower(trim($email));

        $fail = static fn (bool $throttled = false, string $message = 'Invalid credentials.'): array => [
            'success' => false,
            'user_id' => null,
            'remember' => null,
            'throttled' => $throttled,
            'message' => $message,
        ];

        if ($this->isThrottled($email)) {
            Logger::security('Authentication attempt throttled', ['email' => $email]);

            return $fail(true, 'Too many login attempts. Please try again later.');
        }

        if ($email === '' || $password === '') {
            $this->loginAttempts->record($email !== '' ? $email : null, false);
            Logger::security('Authentication attempt rejected: empty credentials', [
                'email' => $email,
            ]);

            return $fail();
        }

        $user = $this->users->findActiveByEmail($email);

        if ($user === null) {
            $this->loginAttempts->record($email, false);
            Logger::security('Authentication attempt failed: unknown user', [
                'email' => $email,
            ]);

            return $fail();
        }

        if (($user['status'] ?? '') === 'suspended') {
            $this->loginAttempts->record($email, false);
            Logger::security('Authentication attempt failed: suspended user', [
                'user_id' => (int) $user['id'],
            ]);

            return $fail(false, 'This account is suspended.');
        }

        $hash = (string) ($user['password_hash'] ?? '');

        if (!$this->hasher->verify($password, $hash)) {
            $this->loginAttempts->record($email, false);
            Logger::security('Authentication attempt failed: invalid password', [
                'user_id' => (int) $user['id'],
            ]);

            return $fail();
        }

        if ($this->hasher->needsRehash($hash)) {
            $this->users->updatePasswordHash((int) $user['id'], $this->hasher->hash($password));
        }

        $this->loginUser($user);
        $this->loginAttempts->record($email, true);

        $rememberPayload = null;

        if ($remember && (bool) config('auth.remember.enabled', true)) {
            $rememberPayload = $this->rememberMe->issue((int) $user['id']);
        }

        Logger::security('Authentication succeeded', [
            'user_id' => (int) $user['id'],
        ]);

        return [
            'success' => true,
            'user_id' => (int) $user['id'],
            'remember' => $rememberPayload,
            'throttled' => false,
            'message' => 'Authenticated.',
        ];
    }

    /**
     * @param  array<string, mixed>  $user
     */
    public function loginUser(array $user): void
    {
        $userId = (int) ($user['id'] ?? 0);

        if ($userId < 1) {
            throw new \InvalidArgumentException('Invalid user payload for login.');
        }

        $roleId = isset($user['role_id']) && is_numeric($user['role_id'])
            ? (int) $user['role_id']
            : null;

        $this->session->establish($userId, $roleId);
        $this->users->touchLastLogin($userId);
    }

    /**
     * End the authenticated session and clear remember-me storage.
     */
    public function logout(): void
    {
        $userId = $this->session->userId();

        if ($userId !== null) {
            $this->rememberMe->clear($userId);
            Logger::security('Authentication logout', ['user_id' => $userId]);
        }

        $this->session->clear();
    }

    public function check(): bool
    {
        return $this->session->check();
    }

    public function id(): ?int
    {
        return $this->session->userId();
    }

    public function roleId(): ?int
    {
        return $this->session->roleId();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function user(): ?array
    {
        $userId = $this->session->userId();

        if ($userId === null) {
            return null;
        }

        return $this->users->findActiveById($userId);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function userById(int $userId): ?array
    {
        return $this->users->findActiveById($userId);
    }

    public function passwordHasher(): PasswordHasher
    {
        return $this->hasher;
    }

    public function loginAttempts(): LoginAttemptService
    {
        return $this->loginAttempts;
    }

    public function rememberMe(): RememberMeService
    {
        return $this->rememberMe;
    }

    public function users(): UserRepository
    {
        return $this->users;
    }
}

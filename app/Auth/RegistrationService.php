<?php

declare(strict_types=1);

namespace JobVisa\App\Auth;

use JobVisa\App\Domain\Security\Services\PasswordPolicy;
use JobVisa\App\Logging\Logger;
use JobVisa\App\Security\Validator;

/**
 * Registers new seeker/employer accounts and issues email verification tokens.
 */
final class RegistrationService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordHasher $hasher,
        private readonly EmailVerificationService $emailVerification,
        private readonly AuthManager $auth,
        private readonly PasswordPolicy $passwordPolicy,
    ) {
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, errors?: array<string, list<string>>, user_id?: int, verification_token?: string, redirect?: array{path: string, url: string, role: string|null}}
     */
    public function register(array $input, bool $autoLogin = true): array
    {
        $min = (int) config('security.password_min_length', 8);
        $validator = Validator::make($input)
            ->required('full_name')
            ->max('full_name', 150)
            ->required('email')
            ->email('email')
            ->max('email', 191)
            ->required('password')
            ->min('password', $min)
            ->max('password', 255)
            ->confirmed('password')
            ->required('role')
            ->in('role', ['seeker', 'employer']);

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ];
        }

        $policyErrors = $this->passwordPolicy->validate((string) $input['password']);
        if ($policyErrors !== []) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => ['password' => $policyErrors],
            ];
        }

        $email = strtolower(trim((string) $input['email']));
        $role = (string) $input['role'];
        $fullName = trim((string) $input['full_name']);
        $password = (string) $input['password'];
        $phone = isset($input['phone']) ? trim((string) $input['phone']) : null;

        if ($phone === '') {
            $phone = null;
        }

        if ($this->users->emailExists($email)) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => ['email' => ['The email has already been taken.']],
            ];
        }

        $roleId = $this->users->findRoleIdBySlug($role);

        $userId = $this->users->create([
            'email' => $email,
            'password_hash' => $this->hasher->hash($password),
            'full_name' => $fullName,
            'phone' => $phone,
            'role' => $role,
            'role_id' => $roleId,
            'status' => 'pending',
        ]);

        $verification = $this->emailVerification->issue($userId);

        Logger::security('User registered', [
            'user_id' => $userId,
            'role' => $role,
        ]);

        $redirect = null;

        if ($autoLogin) {
            $user = $this->users->findActiveById($userId);

            if ($user !== null) {
                $this->auth->loginUser($user);
                $redirect = (new DashboardRedirector())->forUser($user + ['role' => $role]);
            }
        }

        return [
            'success' => true,
            'message' => 'Registration successful. Please verify your email.',
            'user_id' => $userId,
            'verification_token' => $verification['plain'],
            'redirect' => $redirect,
        ];
    }
}

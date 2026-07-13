<?php

declare(strict_types=1);

namespace JobVisa\App\Auth;

use App\Core\Database;
use JobVisa\App\Domain\Security\Services\PasswordPolicy;
use JobVisa\App\Logging\Logger;
use JobVisa\App\Mail\AuthMailer;
use JobVisa\App\Security\RateLimiter;
use JobVisa\App\Security\SecurityHelper;
use JobVisa\App\Security\Validator;

/**
 * Password reset request and confirmation workflow + local mail fallback.
 */
final class PasswordResetService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordHasher $hasher,
        private readonly AuthMailer $mailer,
        private readonly RateLimiter $rateLimiter,
        private readonly PasswordPolicy $passwordPolicy,
    ) {
    }

    /**
     * @return array{success: bool, message: string, errors?: array<string, list<string>>, throttled?: bool}
     */
    public function request(string $email): array
    {
        $email = strtolower(trim($email));

        $generic = [
            'success' => true,
            'message' => 'If the account exists, password reset instructions have been sent.',
        ];

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => ['email' => ['A valid email is required.']],
            ];
        }

        $max = (int) config('auth.password_reset.max_attempts', 5);
        $decay = (int) config('auth.password_reset.decay_seconds', 900);
        $key = 'password_reset:' . $email;

        if ($this->rateLimiter->tooMany($key, $max, $decay)) {
            return [
                'success' => false,
                'message' => 'Too many reset requests. Please try again later.',
                'throttled' => true,
            ];
        }

        $this->rateLimiter->hit($key, $decay);

        $user = $this->users->findActiveByEmail($email);

        if ($user === null) {
            return $generic;
        }

        $minutes = (int) config('auth.password_reset.expire_minutes', 60);
        $plain = SecurityHelper::randomToken(32);
        $hash = hash('sha256', $plain);
        $expiresAt = date('Y-m-d H:i:s', time() + max(1, $minutes) * 60);

        Database::query(
            'UPDATE `password_reset_tokens`
             SET `used_at` = CURRENT_TIMESTAMP
             WHERE `email` = ? AND `used_at` IS NULL',
            [$email]
        );

        Database::query(
            'INSERT INTO `password_reset_tokens`
                (`user_id`, `email`, `token_hash`, `expires_at`, `created_at`)
             VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)',
            [(int) $user['id'], $email, $hash, $expiresAt]
        );

        Logger::security('Password reset requested', [
            'user_id' => (int) $user['id'],
        ]);

        $this->dispatchMail($email, $plain, (string) ($user['full_name'] ?? ''));

        return $generic;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findActiveToken(string $plainToken): ?array
    {
        $plainToken = trim($plainToken);

        if ($plainToken === '') {
            return null;
        }

        $hash = hash('sha256', $plainToken);
        $row = Database::query(
            'SELECT `id`, `user_id`, `email`, `expires_at`, `used_at`
             FROM `password_reset_tokens`
             WHERE `token_hash` = ?
             ORDER BY `id` DESC
             LIMIT 1',
            [$hash]
        )->fetch();

        if ($row === false || $row['used_at'] !== null) {
            return null;
        }

        if (strtotime((string) $row['expires_at']) < time()) {
            return null;
        }

        return $row;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, errors?: array<string, list<string>>}
     */
    public function reset(array $input): array
    {
        $min = (int) config('security.password_min_length', 8);
        $validator = Validator::make($input)
            ->required('token')
            ->required('email')
            ->email('email')
            ->required('password')
            ->min('password', $min)
            ->confirmed('password');

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
        $plain = trim((string) $input['token']);
        $hash = hash('sha256', $plain);

        $row = Database::query(
            'SELECT `id`, `user_id`, `expires_at`, `used_at`
             FROM `password_reset_tokens`
             WHERE `email` = ? AND `token_hash` = ?
             ORDER BY `id` DESC
             LIMIT 1',
            [$email, $hash]
        )->fetch();

        if ($row === false) {
            return ['success' => false, 'message' => 'Invalid or expired password reset token.'];
        }

        if ($row['used_at'] !== null) {
            return ['success' => false, 'message' => 'This password reset link has already been used.'];
        }

        if (strtotime((string) $row['expires_at']) < time()) {
            return ['success' => false, 'message' => 'This password reset link has expired.'];
        }

        $user = $this->users->findActiveByEmail($email);

        if ($user === null) {
            return ['success' => false, 'message' => 'Invalid or expired password reset token.'];
        }

        $this->users->updatePasswordHash((int) $user['id'], $this->hasher->hash((string) $input['password']));
        $this->users->updateRememberToken((int) $user['id'], null);

        Database::query(
            'UPDATE `password_reset_tokens` SET `used_at` = CURRENT_TIMESTAMP WHERE `id` = ?',
            [(int) $row['id']]
        );

        Logger::security('Password reset completed', [
            'user_id' => (int) $user['id'],
        ]);

        return ['success' => true, 'message' => 'Password has been reset successfully.'];
    }

    private function dispatchMail(string $email, string $plainToken, string $name): void
    {
        $link = app_url('/reset-password/' . rawurlencode($plainToken));
        $safeName = $name !== '' ? $name : 'there';
        $body = '<p>Hello ' . htmlspecialchars($safeName, ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p>Reset your JobVisa.lk password using this link:</p>'
            . '<p><a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '</a></p>'
            . '<p>If you did not request this, you can ignore this message.</p>';

        $context = ['type' => 'password_reset'];

        if ((string) config('app.env', 'production') !== 'production' || (bool) config('app.debug', false)) {
            $context['reset_link'] = $link;
        }

        $this->mailer->send($email, 'Reset your JobVisa.lk password', $body, $context);
    }
}

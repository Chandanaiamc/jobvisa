<?php

declare(strict_types=1);

namespace JobVisa\App\Auth;

use App\Core\Database;
use JobVisa\App\Logging\Logger;
use JobVisa\App\Mail\AuthMailer;
use JobVisa\App\Security\RateLimiter;
use JobVisa\App\Security\SecurityHelper;

/**
 * Email verification token workflow (hash-at-rest) + local mail fallback.
 */
final class EmailVerificationService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly AuthMailer $mailer,
        private readonly RateLimiter $rateLimiter
    ) {
    }

    /**
     * @return array{plain: string, hash: string, expires_at: string}
     */
    public function issue(int $userId, bool $sendMail = true): array
    {
        if ($userId < 1) {
            throw new \InvalidArgumentException('Invalid user id for email verification.');
        }

        $hours = (int) config('auth.email_verification.expire_hours', 48);
        $plain = SecurityHelper::randomToken(32);
        $hash = hash('sha256', $plain);
        $expiresAt = date('Y-m-d H:i:s', time() + max(1, $hours) * 3600);

        Database::query(
            'UPDATE `email_verification_tokens`
             SET `verified_at` = CURRENT_TIMESTAMP
             WHERE `user_id` = ? AND `verified_at` IS NULL',
            [$userId]
        );

        Database::query(
            'INSERT INTO `email_verification_tokens`
                (`user_id`, `token_hash`, `expires_at`, `created_at`)
             VALUES (?, ?, ?, CURRENT_TIMESTAMP)',
            [$userId, $hash, $expiresAt]
        );

        if ($sendMail) {
            $user = $this->users->findActiveById($userId);

            if ($user !== null) {
                $this->dispatchMail((string) $user['email'], $plain, (string) ($user['full_name'] ?? ''));
            }
        }

        return [
            'plain' => $plain,
            'hash' => $hash,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function verify(string $plainToken): array
    {
        $plainToken = trim($plainToken);

        if ($plainToken === '') {
            return ['success' => false, 'message' => 'Verification token is required.'];
        }

        $hash = hash('sha256', $plainToken);

        $row = Database::query(
            'SELECT `id`, `user_id`, `expires_at`, `verified_at`
             FROM `email_verification_tokens`
             WHERE `token_hash` = ?
             ORDER BY `id` DESC
             LIMIT 1',
            [$hash]
        )->fetch();

        if ($row === false) {
            return ['success' => false, 'message' => 'Invalid verification token.'];
        }

        if ($row['verified_at'] !== null) {
            return ['success' => false, 'message' => 'Verification token already used.'];
        }

        if (strtotime((string) $row['expires_at']) < time()) {
            return ['success' => false, 'message' => 'Verification token has expired.'];
        }

        $userId = (int) $row['user_id'];

        Database::query(
            'UPDATE `email_verification_tokens`
             SET `verified_at` = CURRENT_TIMESTAMP
             WHERE `id` = ?',
            [(int) $row['id']]
        );

        $this->users->markEmailVerified($userId);

        Logger::security('Email verified', ['user_id' => $userId]);

        return ['success' => true, 'message' => 'Email verified successfully.'];
    }

    /**
     * @return array{success: bool, message: string, throttled?: bool}
     */
    public function resend(string $email): array
    {
        $email = strtolower(trim($email));
        $generic = [
            'success' => true,
            'message' => 'If the account exists and is unverified, a new verification link was sent.',
        ];

        $max = (int) config('auth.email_verification.resend_max_attempts', 3);
        $decay = (int) config('auth.email_verification.resend_decay_seconds', 600);
        $key = 'email_verify_resend:' . $email;

        if ($this->rateLimiter->tooMany($key, $max, $decay)) {
            return [
                'success' => false,
                'message' => 'Too many verification emails requested. Please try again later.',
                'throttled' => true,
            ];
        }

        $this->rateLimiter->hit($key, $decay);

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return $generic;
        }

        $user = $this->users->findActiveByEmail($email);

        if ($user === null || !empty($user['email_verified_at'])) {
            return $generic;
        }

        $this->issue((int) $user['id'], true);

        return $generic;
    }

    /**
     * Resend for the currently authenticated user.
     *
     * @return array{success: bool, message: string, throttled?: bool}
     */
    public function resendForUser(array $user): array
    {
        $email = strtolower(trim((string) ($user['email'] ?? '')));

        if ($email === '') {
            return ['success' => false, 'message' => 'Unable to resend verification email.'];
        }

        if (!empty($user['email_verified_at'])) {
            return ['success' => true, 'message' => 'Your email is already verified.'];
        }

        return $this->resend($email);
    }

    private function dispatchMail(string $email, string $plainToken, string $name): void
    {
        $link = app_url('/email/verify/' . rawurlencode($plainToken));
        $safeName = $name !== '' ? $name : 'there';
        $body = '<p>Hello ' . htmlspecialchars($safeName, ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p>Please verify your JobVisa.lk email address:</p>'
            . '<p><a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '</a></p>'
            . '<p>This link expires according to platform policy.</p>';

        $context = ['type' => 'email_verification'];

        if ((string) config('app.env', 'production') !== 'production' || (bool) config('app.debug', false)) {
            $context['verification_link'] = $link;
        }

        $this->mailer->send($email, 'Verify your JobVisa.lk email', $body, $context);
    }
}

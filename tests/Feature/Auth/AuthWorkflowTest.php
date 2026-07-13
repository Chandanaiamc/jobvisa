<?php

declare(strict_types=1);

namespace JobVisa\Tests\Feature\Auth;

use App\Core\Database;
use JobVisa\App\Auth\AuthManager;
use JobVisa\App\Auth\EmailVerificationService;
use JobVisa\App\Auth\PasswordResetService;
use JobVisa\App\Auth\RegistrationService;
use JobVisa\App\Security\SecurityHelper;
use JobVisa\Tests\Support\ApplicationTestCase;
use Throwable;

final class AuthWorkflowTest extends ApplicationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        try {
            Database::connection()->query('SELECT 1');
        } catch (Throwable $exception) {
            $this->markTestSkipped('Database unavailable: ' . $exception->getMessage());
        }
    }

    public function testRegisterLoginVerifyResetLogoutFlow(): void
    {
        $email = 'sprint1_' . bin2hex(random_bytes(4)) . '@example.test';

        /** @var RegistrationService $registration */
        $registration = $this->container->get(RegistrationService::class);
        $registered = $registration->register([
            'full_name' => 'Sprint One User',
            'email' => $email,
            'password' => 'SecretPass!123',
            'password_confirmation' => 'SecretPass!123',
            'role' => 'seeker',
        ], false);

        $this->assertTrue($registered['success']);
        $this->assertArrayHasKey('verification_token', $registered);

        /** @var EmailVerificationService $verification */
        $verification = $this->container->get(EmailVerificationService::class);
        $verified = $verification->verify((string) $registered['verification_token']);
        $this->assertTrue($verified['success']);

        /** @var AuthManager $auth */
        $auth = $this->container->get(AuthManager::class);
        $login = $auth->attempt($email, 'SecretPass!123', true);
        $this->assertTrue($login['success']);
        $this->assertTrue($auth->check());

        /** @var PasswordResetService $resets */
        $resets = $this->container->get(PasswordResetService::class);
        $forgot = $resets->request($email);
        $this->assertTrue($forgot['success']);
        $this->assertArrayNotHasKey('reset_token', $forgot);

        // Simulate the emailed plain token (service stores only the hash).
        $plainReset = SecurityHelper::randomToken(32);
        $userId = (int) $registered['user_id'];
        Database::query(
            'UPDATE `password_reset_tokens` SET `used_at` = CURRENT_TIMESTAMP WHERE `email` = ? AND `used_at` IS NULL',
            [$email]
        );
        Database::query(
            'INSERT INTO `password_reset_tokens`
                (`user_id`, `email`, `token_hash`, `expires_at`, `created_at`)
             VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)',
            [$userId, $email, hash('sha256', $plainReset), date('Y-m-d H:i:s', time() + 3600)]
        );

        $reset = $resets->reset([
            'email' => $email,
            'token' => $plainReset,
            'password' => 'NewSecret!456',
            'password_confirmation' => 'NewSecret!456',
        ]);
        $this->assertTrue($reset['success']);

        $auth->logout();
        $this->assertFalse($auth->check());

        $loginAgain = $auth->attempt($email, 'NewSecret!456');
        $this->assertTrue($loginAgain['success']);
        $auth->logout();
    }

    public function testAuthServicesResolveFromContainer(): void
    {
        $classes = [
            AuthManager::class,
            RegistrationService::class,
            PasswordResetService::class,
            EmailVerificationService::class,
        ];

        foreach ($classes as $class) {
            $this->assertIsObject($this->container->get($class));
        }
    }
}

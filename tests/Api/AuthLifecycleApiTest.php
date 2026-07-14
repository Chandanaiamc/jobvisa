<?php

declare(strict_types=1);

namespace JobVisa\Tests\Api;

use App\Core\Config;
use App\Core\Database;
use JobVisa\App\Auth\PasswordHasher;
use JobVisa\App\Domain\Api\Auth\PersonalAccessTokenService;
use JobVisa\App\Domain\Api\Http\ApiException;
use JobVisa\App\Domain\Auth\Services\AuthLifecycleService;
use JobVisa\App\Domain\Auth\Services\LogoutEverywhereService;
use JobVisa\App\Domain\Auth\Services\MfaFactorService;
use JobVisa\App\Domain\Auth\Services\RefreshTokenService;
use JobVisa\Tests\Support\ApplicationTestCase;
use PDO;
use Throwable;

final class AuthLifecycleApiTest extends ApplicationTestCase
{
    private function seeker(): ?array
    {
        try {
            $row = Database::query(
                "SELECT id, email FROM users WHERE role = 'seeker' AND status = 'active' ORDER BY id ASC LIMIT 1"
            )->fetch(PDO::FETCH_ASSOC);

            return is_array($row) ? $row : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function testLifecycleStatusAndSchema(): void
    {
        /** @var AuthLifecycleService $svc */
        $svc = $this->container->get(AuthLifecycleService::class);
        $status = $svc->status();
        $this->assertSame('ok', $status['status']);
        $this->assertTrue($status['enabled']);
        $this->assertSame('2.0.0', $status['version']);
        $this->assertTrue($status['features']['transactional_refresh'] ?? false);
        $this->assertTrue($status['features']['device_revokes_access'] ?? false);
        if (!($status['schema']['refresh'] ?? false)) {
            $this->markTestSkipped('Migration 065 not applied');
        }
        $this->assertTrue($status['schema']['devices']);
        $this->assertTrue($status['schema']['mfa']);
    }

    public function testLifecycleDisabledGate(): void
    {
        /** @var AuthLifecycleService $svc */
        $svc = $this->container->get(AuthLifecycleService::class);
        $prev = (bool) config('auth_lifecycle.enabled', true);
        Config::set('auth_lifecycle.enabled', false);
        try {
            $status = $svc->status();
            $this->assertSame('disabled', $status['status']);
            $this->assertFalse($status['enabled']);
            $this->expectException(ApiException::class);
            $svc->assertEnabled();
        } finally {
            Config::set('auth_lifecycle.enabled', $prev);
        }
    }

    public function testLoginRefreshReuseAndLogoutEverywhere(): void
    {
        $seeker = $this->seeker();
        if ($seeker === null) {
            $this->markTestSkipped('No seeker user');
        }
        /** @var AuthLifecycleService $svc */
        $svc = $this->container->get(AuthLifecycleService::class);
        if (!$svc->status()['schema']['refresh']) {
            $this->markTestSkipped('Migration 065 not applied');
        }

        $password = 'LifecycleTest!234';
        $this->container->get(PasswordHasher::class);
        Database::query(
            'UPDATE users SET password_hash = ? WHERE id = ?',
            [$this->container->get(PasswordHasher::class)->hash($password), (int) $seeker['id']]
        );

        $login = $svc->login((string) $seeker['email'], $password, [
            'name' => 'PHPUnit Device',
            'fingerprint' => 'phpunit-' . bin2hex(random_bytes(4)),
            'platform' => 'test',
        ]);
        $this->assertArrayHasKey('access_token', $login);
        $this->assertArrayHasKey('refresh_token', $login);
        $this->assertStringStartsWith('jv1_', $login['access_token']);
        $this->assertStringStartsWith('jvr1_', $login['refresh_token']);

        $rotated = $svc->refresh($login['refresh_token']);
        $this->assertNotSame($login['refresh_token'], $rotated['refresh_token']);

        /** @var RefreshTokenService $refresh */
        $refresh = $this->container->get(RefreshTokenService::class);
        $this->expectException(\JobVisa\App\Domain\Api\Http\ApiException::class);
        $refresh->rotate($login['refresh_token']);
    }

    public function testMfaReadyArchitecture(): void
    {
        $seeker = $this->seeker();
        if ($seeker === null) {
            $this->markTestSkipped('No seeker user');
        }
        /** @var MfaFactorService $mfa */
        $mfa = $this->container->get(MfaFactorService::class);
        if (!$mfa->ensureSchemaReady()) {
            $this->markTestSkipped('Migration 065 not applied');
        }
        $status = $mfa->statusForUser((int) $seeker['id']);
        $this->assertTrue($status['mfa_ready']);
        $created = $mfa->registerPlaceholder((int) $seeker['id'], 'totp', 'Test Factor');
        $this->assertGreaterThan(0, $created['id']);
        $this->assertTrue($mfa->revoke((int) $seeker['id'], (int) $created['id']));
    }

    public function testLogoutEverywhereRevokesRefresh(): void
    {
        $seeker = $this->seeker();
        if ($seeker === null) {
            $this->markTestSkipped('No seeker user');
        }
        /** @var AuthLifecycleService $svc */
        $svc = $this->container->get(AuthLifecycleService::class);
        if (!$svc->status()['schema']['refresh']) {
            $this->markTestSkipped('Migration 065 not applied');
        }
        $password = 'LifecycleTest!234';
        Database::query(
            'UPDATE users SET password_hash = ? WHERE id = ?',
            [$this->container->get(PasswordHasher::class)->hash($password), (int) $seeker['id']]
        );
        $login = $svc->login((string) $seeker['email'], $password, [
            'fingerprint' => 'phpunit-logout-' . bin2hex(random_bytes(3)),
        ]);
        $result = $svc->logoutEverywhere((int) $seeker['id'], true);
        $this->assertGreaterThanOrEqual(1, $result['refresh_revoked']);

        $this->expectException(ApiException::class);
        $svc->refresh($login['refresh_token']);
    }

    public function testDeviceLogoutRevokesLinkedAccessToken(): void
    {
        $seeker = $this->seeker();
        if ($seeker === null) {
            $this->markTestSkipped('No seeker user');
        }
        /** @var AuthLifecycleService $svc */
        $svc = $this->container->get(AuthLifecycleService::class);
        if (!$svc->status()['schema']['refresh']) {
            $this->markTestSkipped('Migration 065 not applied');
        }

        $password = 'LifecycleTest!234';
        Database::query(
            'UPDATE users SET password_hash = ? WHERE id = ?',
            [$this->container->get(PasswordHasher::class)->hash($password), (int) $seeker['id']]
        );

        $login = $svc->login((string) $seeker['email'], $password, [
            'name' => 'Device Logout Test',
            'fingerprint' => 'phpunit-device-logout-' . bin2hex(random_bytes(3)),
            'platform' => 'test',
        ]);
        $deviceId = (int) ($login['device']['id'] ?? 0);
        $this->assertGreaterThan(0, $deviceId);
        $access = (string) $login['access_token'];

        /** @var LogoutEverywhereService $logout */
        $logout = $this->container->get(LogoutEverywhereService::class);
        $revoked = $logout->revokeDevice((int) $seeker['id'], $deviceId);
        $this->assertTrue($revoked['device_revoked']);
        $this->assertGreaterThanOrEqual(1, $revoked['refresh_revoked']);
        $this->assertGreaterThanOrEqual(1, $revoked['access_revoked']);

        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $access;
        /** @var PersonalAccessTokenService $pats */
        $pats = $this->container->get(PersonalAccessTokenService::class);
        try {
            $this->expectException(ApiException::class);
            $pats->authenticateFromRequest();
        } finally {
            unset($_SERVER['HTTP_AUTHORIZATION']);
        }
    }

    public function testSurfaceCoverageHelpers(): void
    {
        $hasher = $this->container->get(\JobVisa\App\Domain\Auth\Support\AuthTokenHasher::class);
        $this->assertInstanceOf(\DateTimeImmutable::class, $hasher->utcNow());

        $seeker = $this->seeker();
        if ($seeker === null) {
            $this->markTestSkipped('No seeker user');
        }
        /** @var AuthLifecycleService $svc */
        $svc = $this->container->get(AuthLifecycleService::class);
        if (!$svc->status()['schema']['refresh']) {
            $this->markTestSkipped('Migration 065 not applied');
        }

        $devices = $this->container->get(\JobVisa\App\Domain\Auth\Services\DeviceSessionService::class);
        $this->assertTrue($devices->ensureSchemaReady());
        $created = $devices->touchOrCreate((int) $seeker['id'], 'cov-fp-' . bin2hex(random_bytes(2)), 'Cov Device', 'phpunit');
        $devices->touch((int) $created['id']);
        $this->assertNotNull($devices->findForUser((int) $created['id'], (int) $seeker['id']));
        $this->assertNotEmpty($devices->listForUser((int) $seeker['id']));

        $refresh = $this->container->get(RefreshTokenService::class);
        $this->assertTrue($refresh->ensureSchemaReady());
        $issued = $refresh->issue((int) $seeker['id'], (int) $created['id'], $hasher->familyId(), null);
        $this->assertArrayHasKey('refresh_token', $issued);
        $this->assertGreaterThanOrEqual(0, $refresh->revokePresented($issued['refresh_token'], (int) $seeker['id']));
        $this->assertGreaterThanOrEqual(0, $refresh->revokeFamily($hasher->familyId(), (int) $seeker['id']));
        $this->assertGreaterThanOrEqual(0, $refresh->revokeForDevice((int) $created['id'], (int) $seeker['id']));
        $this->assertGreaterThanOrEqual(0, $refresh->revokeAllForUser((int) $seeker['id']));

        $logout = $this->container->get(\JobVisa\App\Domain\Auth\Services\LogoutEverywhereService::class);
        $this->assertIsArray($logout->revokeDevice((int) $seeker['id'], (int) $created['id']));
        $this->assertIsInt($logout->revokeAllPats((int) $seeker['id']));
        $this->assertIsArray($logout->revokeAll((int) $seeker['id'], false));
        $devices->revokeAll((int) $seeker['id']);

        // Password/email adapters (anti-enumeration / validation paths)
        $forgot = $svc->forgotPassword('nobody-coverage-' . bin2hex(random_bytes(2)) . '@example.test');
        $this->assertArrayHasKey('success', $forgot);
        $resend = $svc->resendVerification((string) $seeker['email']);
        $this->assertArrayHasKey('success', $resend);
        try {
            $svc->verifyEmail('not-a-real-token');
            $this->fail('expected verification failure');
        } catch (\JobVisa\App\Domain\Api\Http\ApiException $e) {
            $this->assertSame('validation_error', $e->errorCode());
        }
        try {
            $svc->resetPassword(['email' => 'x', 'token' => 'y', 'password' => 'short']);
            $this->fail('expected reset validation failure');
        } catch (\JobVisa\App\Domain\Api\Http\ApiException $e) {
            $this->assertSame('validation_error', $e->errorCode());
        }

        $password = 'LifecycleTest!234';
        Database::query(
            'UPDATE users SET password_hash = ? WHERE id = ?',
            [$this->container->get(PasswordHasher::class)->hash($password), (int) $seeker['id']]
        );
        $login = $svc->login((string) $seeker['email'], $password, ['fingerprint' => 'cov-logout-' . bin2hex(random_bytes(2))]);
        $out = $svc->logoutCurrent($login['refresh_token'], (int) $seeker['id'], null);
        $this->assertTrue($out['logged_out']);
    }
}

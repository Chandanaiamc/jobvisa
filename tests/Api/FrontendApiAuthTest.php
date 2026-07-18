<?php

declare(strict_types=1);

namespace JobVisa\Tests\Api;

use App\Core\Database;
use JobVisa\App\Auth\PasswordHasher;
use JobVisa\App\Domain\Api\Http\ApiException;
use JobVisa\App\Domain\Frontend\Auth\ApiAuthTokenCookie;
use JobVisa\App\Domain\Frontend\Auth\FrontendApiAuthService;
use JobVisa\Tests\Support\ApplicationTestCase;
use PDO;
use Throwable;

final class FrontendApiAuthTest extends ApplicationTestCase
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

    private function setPassword(array $seeker, string $password = 'FrontendApiAuth!234'): void
    {
        Database::query(
            'UPDATE users SET password_hash = ? WHERE id = ?',
            [$this->container->get(PasswordHasher::class)->hash($password), (int) $seeker['id']]
        );
    }

    private function clearCookies(): void
    {
        $cookies = $this->container->get(ApiAuthTokenCookie::class);
        unset($_COOKIE[$cookies->accessCookieName()], $_COOKIE[$cookies->refreshCookieName()]);
        $cookies->clear();
    }

    public function testLoginMeRefreshLogout(): void
    {
        $seeker = $this->seeker();
        if ($seeker === null) {
            $this->markTestSkipped('No seeker user');
        }

        /** @var FrontendApiAuthService $svc */
        $svc = $this->container->get(FrontendApiAuthService::class);
        $this->clearCookies();
        $password = 'FrontendApiAuth!234';
        $this->setPassword($seeker, $password);

        $login = $svc->login((string) $seeker['email'], $password, [
            'name' => 'PHPUnit FE',
            'fingerprint' => 'fe-auth-' . bin2hex(random_bytes(3)),
            'platform' => 'test',
        ]);

        $this->assertArrayHasKey('user', $login);
        $this->assertSame((int) $seeker['id'], (int) $login['user']['id']);
        $this->assertArrayNotHasKey('access_token', $login);
        $this->assertArrayNotHasKey('refresh_token', $login);

        $cookies = $this->container->get(ApiAuthTokenCookie::class);
        $this->assertNotNull($cookies->access());
        $this->assertNotNull($cookies->refresh());
        $this->assertStringStartsWith('jv1_', (string) $cookies->access());
        $this->assertStringStartsWith('jvr1_', (string) $cookies->refresh());

        $me = $svc->me();
        $this->assertSame((int) $seeker['id'], (int) $me['user']['id']);

        $oldAccess = $cookies->access();
        $oldRefresh = $cookies->refresh();
        $rotated = $svc->refresh();
        $this->assertArrayHasKey('access_expires_at', $rotated);
        $this->assertNotSame($oldAccess, $cookies->access());
        $this->assertNotSame($oldRefresh, $cookies->refresh());

        $out = $svc->logout(true);
        $this->assertTrue($out['logged_out']);
        $this->assertNull($cookies->access());
        $this->assertNull($cookies->refresh());
    }

    public function testInvalidCredentials(): void
    {
        $seeker = $this->seeker();
        if ($seeker === null) {
            $this->markTestSkipped('No seeker user');
        }

        /** @var FrontendApiAuthService $svc */
        $svc = $this->container->get(FrontendApiAuthService::class);
        $this->clearCookies();
        $this->setPassword($seeker, 'FrontendApiAuth!234');

        $this->expectException(ApiException::class);
        $svc->login((string) $seeker['email'], 'DefinitelyWrongPassword!!!', [
            'fingerprint' => 'fe-bad-' . bin2hex(random_bytes(2)),
        ]);
    }

    public function testExpiredAccessToken(): void
    {
        $seeker = $this->seeker();
        if ($seeker === null) {
            $this->markTestSkipped('No seeker user');
        }

        /** @var FrontendApiAuthService $svc */
        $svc = $this->container->get(FrontendApiAuthService::class);
        $this->clearCookies();
        $password = 'FrontendApiAuth!234';
        $this->setPassword($seeker, $password);

        $svc->login((string) $seeker['email'], $password, [
            'fingerprint' => 'fe-exp-' . bin2hex(random_bytes(2)),
        ]);

        $cookies = $this->container->get(ApiAuthTokenCookie::class);
        $access = (string) $cookies->access();
        $hash = $this->container->get(\JobVisa\App\Domain\Api\Auth\PersonalAccessTokenService::class)->hash($access);
        Database::query(
            'UPDATE api_personal_access_tokens SET expires_at = DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 MINUTE)
             WHERE token_hash = ?',
            [$hash]
        );

        $this->expectException(ApiException::class);
        $svc->me();
    }

    public function testFailedRefresh(): void
    {
        /** @var FrontendApiAuthService $svc */
        $svc = $this->container->get(FrontendApiAuthService::class);
        $this->clearCookies();
        $_COOKIE[$this->container->get(ApiAuthTokenCookie::class)->refreshCookieName()] = 'jvr1_not_a_real_token';

        $this->expectException(ApiException::class);
        $svc->refresh();
    }

    public function testBridgeSessionToApi(): void
    {
        $seeker = $this->seeker();
        if ($seeker === null) {
            $this->markTestSkipped('No seeker user');
        }
        /** @var FrontendApiAuthService $svc */
        $svc = $this->container->get(FrontendApiAuthService::class);
        $this->clearCookies();

        $svc->bridgeSessionToApi((int) $seeker['id'], [
            'name' => 'Bridge Test',
            'fingerprint' => 'fe-bridge-' . bin2hex(random_bytes(2)),
            'platform' => 'test',
        ]);

        $cookies = $this->container->get(ApiAuthTokenCookie::class);
        $this->assertNotNull($cookies->access());
        $this->assertNotNull($cookies->refresh());
        $svc->clearApiSessionOnWebLogout();
        $this->assertNull($cookies->access());
    }

    public function testBridgeDoesNotExposeTokensInLoginPayload(): void
    {
        $seeker = $this->seeker();
        if ($seeker === null) {
            $this->markTestSkipped('No seeker user');
        }
        /** @var FrontendApiAuthService $svc */
        $svc = $this->container->get(FrontendApiAuthService::class);
        $this->clearCookies();
        $this->setPassword($seeker, 'FrontendApiAuth!234');
        $login = $svc->login((string) $seeker['email'], 'FrontendApiAuth!234', [
            'fingerprint' => 'fe-safe-' . bin2hex(random_bytes(2)),
        ]);
        $encoded = json_encode($login);
        $this->assertIsString($encoded);
        $this->assertStringNotContainsString('jv1_', $encoded);
        $this->assertStringNotContainsString('jvr1_', $encoded);
        $svc->logout(true);
    }
}

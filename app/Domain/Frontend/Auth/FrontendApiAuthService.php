<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Frontend\Auth;

use JobVisa\App\Auth\AuthManager;
use JobVisa\App\Auth\DashboardRedirector;
use JobVisa\App\Auth\RememberMeCookie;
use JobVisa\App\Auth\RememberMeService;
use JobVisa\App\Auth\UserRepository;
use JobVisa\App\Domain\Api\Auth\PersonalAccessTokenRepository;
use JobVisa\App\Domain\Api\Auth\PersonalAccessTokenService;
use JobVisa\App\Domain\Api\Http\ApiException;
use JobVisa\App\Domain\Api\Resources\ApiResource;
use JobVisa\App\Domain\Auth\Services\AuthLifecycleService;
use JobVisa\App\Security\SecurityHelper;

/**
 * Same-origin bridge: AuthLifecycleService ↔ httpOnly cookies ↔ PHP session.
 * Tokens are never returned in JSON to the browser.
 */
final class FrontendApiAuthService
{
    public function __construct(
        private readonly AuthLifecycleService $lifecycle,
        private readonly AuthManager $auth,
        private readonly UserRepository $users,
        private readonly ApiAuthTokenCookie $cookies,
        private readonly PersonalAccessTokenService $pats,
        private readonly PersonalAccessTokenRepository $patRepo,
        private readonly DashboardRedirector $redirector,
        private readonly RememberMeService $rememberMe,
        private readonly RememberMeCookie $rememberCookie,
    ) {
    }

    /**
     * Password login → lifecycle tokens in cookies + web session.
     *
     * @param  array<string, mixed>  $device
     * @return array{user: array<string, mixed>, redirect: array<string, mixed>, access_expires_at: string, device: array<string, mixed>|null, email_verified: bool}
     */
    public function login(string $email, string $password, array $device = [], bool $remember = false): array
    {
        $bundle = $this->lifecycle->login($email, $password, $device);
        $this->cookies->queueFromBundle($bundle);

        $userPayload = is_array($bundle['user'] ?? null) ? $bundle['user'] : [];
        $userId = (int) ($userPayload['id'] ?? 0);
        $user = $userId > 0 ? $this->users->findActiveById($userId) : null;
        if ($user === null) {
            throw ApiException::unauthorized('Unable to establish session.');
        }

        $this->auth->loginUser($user);

        if ($remember) {
            $issued = $this->rememberMe->issue($userId);
            $days = (int) config('auth.remember.cookie_days', 30);
            $this->rememberCookie->queue($userId, (string) $issued['plain'], $days);
        }

        unset($user['password_hash']);

        return [
            'user' => ApiResource::user($user),
            'redirect' => $this->redirector->forUser($user),
            'access_expires_at' => (string) ($bundle['access_expires_at'] ?? ''),
            'device' => is_array($bundle['device'] ?? null) ? $bundle['device'] : null,
            'email_verified' => !empty($user['email_verified_at']),
        ];
    }

    /**
     * After classic session login, mint API cookies without re-checking password.
     *
     * @param  array<string, mixed>  $device
     */
    public function bridgeSessionToApi(int $userId, array $device = []): void
    {
        if ($userId < 1) {
            return;
        }
        if (!(bool) config('frontend.api_auth.enabled', true)) {
            return;
        }
        if (!(bool) config('auth_lifecycle.enabled', true)) {
            return;
        }

        try {
            $bundle = $this->lifecycle->issueTokensForUser($userId, $device, 'web_bridge');
            $this->cookies->queueFromBundle($bundle);
        } catch (\Throwable) {
            // Non-fatal: session MVC login must still succeed if lifecycle schema is unavailable.
        }
    }

    /**
     * Rotate refresh cookie → new access/refresh cookies.
     *
     * @return array{access_expires_at: string, device_id: int|null}
     */
    public function refresh(): array
    {
        $refresh = $this->cookies->refresh();
        if ($refresh === null || $refresh === '') {
            throw ApiException::unauthorized('Refresh token missing.');
        }

        $bundle = $this->lifecycle->refresh($refresh);
        $this->cookies->queueFromBundle($bundle);

        return [
            'access_expires_at' => (string) ($bundle['access_expires_at'] ?? ''),
            'device_id' => isset($bundle['device_id']) ? (int) $bundle['device_id'] : null,
        ];
    }

    /**
     * Current user via access cookie.
     *
     * @return array{user: array<string, mixed>, token: array<string, mixed>}
     */
    public function me(): array
    {
        $access = $this->cookies->access();
        if ($access === null || $access === '') {
            throw ApiException::unauthorized('Access token missing.');
        }

        $row = $this->resolveAccessRow($access);
        $user = $this->users->findActiveById((int) $row['user_id']);
        if ($user === null) {
            throw ApiException::unauthorized('Token user is inactive.');
        }
        unset($user['password_hash']);

        $ip = SecurityHelper::clientIp();
        $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $this->patRepo->touchUsage((int) $row['id'], $ip, $ua);

        return [
            'user' => ApiResource::user($user),
            'token' => ApiResource::tokenMeta($row),
        ];
    }

    /**
     * Revoke current API session cookies + optional web session.
     *
     * @return array{logged_out: bool, refresh_revoked: int, session_cleared: bool}
     */
    public function logout(bool $clearWebSession = true): array
    {
        $access = $this->cookies->access();
        $refresh = $this->cookies->refresh();
        $userId = $this->auth->id() ?? 0;
        $accessTokenId = null;
        $refreshRevoked = 0;

        if (is_string($access) && $access !== '') {
            try {
                $row = $this->resolveAccessRow($access);
                $accessTokenId = (int) ($row['id'] ?? 0);
                if ($userId < 1) {
                    $userId = (int) ($row['user_id'] ?? 0);
                }
            } catch (ApiException) {
                // Still clear cookies / try refresh revoke.
            }
        }

        if ($userId > 0) {
            try {
                $out = $this->lifecycle->logoutCurrent(
                    is_string($refresh) && $refresh !== '' ? $refresh : null,
                    $userId,
                    $accessTokenId !== null && $accessTokenId > 0 ? $accessTokenId : null
                );
                $refreshRevoked = (int) ($out['refresh_revoked'] ?? 0);
            } catch (\Throwable) {
                $refreshRevoked = 0;
            }
        }

        $this->cookies->clear();

        $sessionCleared = false;
        if ($clearWebSession && $this->auth->check()) {
            $this->auth->logout();
            $this->rememberCookie->forget();
            $sessionCleared = true;
        }

        return [
            'logged_out' => true,
            'refresh_revoked' => $refreshRevoked,
            'session_cleared' => $sessionCleared,
        ];
    }

    /**
     * Clear API cookies during classic web logout (best-effort revoke).
     */
    public function clearApiSessionOnWebLogout(): void
    {
        if (!(bool) config('frontend.api_auth.enabled', true)) {
            $this->cookies->clear();

            return;
        }

        try {
            $this->logout(false);
        } catch (\Throwable) {
            $this->cookies->clear();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveAccessRow(string $plain): array
    {
        if (!$this->patRepo->ensureSchemaReady()) {
            throw ApiException::unauthorized('API authentication unavailable.');
        }

        $row = $this->patRepo->findByHash($this->pats->hash($plain));
        if ($row === null) {
            throw ApiException::unauthorized('Invalid access token.');
        }
        if (!empty($row['revoked_at'])) {
            throw ApiException::tokenRevoked();
        }
        if ($this->pats->isExpired($row['expires_at'] ?? null)) {
            throw ApiException::tokenExpired();
        }

        return $row;
    }
}

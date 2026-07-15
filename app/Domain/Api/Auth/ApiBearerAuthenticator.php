<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Api\Auth;

use JobVisa\App\Domain\Api\Http\ApiException;
use JobVisa\App\Domain\Auth\Services\AccessTokenService;

/**
 * Distinguishes short-lived session access tokens from long-lived PATs.
 *
 * Access tokens (default prefix jva1_) use AccessTokenService validation.
 * PATs (default prefix jv1_) use PersonalAccessTokenService validation.
 * Refresh tokens (jvr1_) are never accepted as Bearer credentials.
 */
final class ApiBearerAuthenticator
{
    public function __construct(
        private readonly AccessTokenService $accessTokens,
        private readonly PersonalAccessTokenService $pats,
    ) {
    }

    public function authenticateFromRequest(): void
    {
        $header = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
        if (!preg_match('/^\s*Bearer\s+(\S+)\s*$/i', $header, $m)) {
            throw ApiException::unauthorized('Bearer token required.');
        }

        $plain = $m[1];
        $refreshPrefix = (string) config('auth_lifecycle.refresh_prefix', 'jvr1_');
        if ($refreshPrefix !== '' && str_starts_with($plain, $refreshPrefix)) {
            throw ApiException::unauthorized('Refresh tokens cannot be used as Bearer credentials.');
        }

        $accessPrefix = (string) config('auth_lifecycle.access_prefix', 'jva1_');
        if ($accessPrefix !== '' && str_starts_with($plain, $accessPrefix)) {
            $this->accessTokens->authenticatePlain($plain);

            return;
        }

        // Long-lived PATs (and legacy Phase-1 jv1_ session rows still in the PAT table).
        $this->pats->authenticatePlain($plain, 'pat');
    }
}

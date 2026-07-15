<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use JobVisa\App\Domain\Api\Auth\ApiAuth;
use JobVisa\App\Domain\Api\Http\ApiException;
use JobVisa\App\Domain\Auth\Services\AuthLifecycleService;
use JobVisa\App\Domain\Auth\Services\DeviceSessionService;
use JobVisa\App\Domain\Auth\Services\LogoutEverywhereService;
use JobVisa\App\Domain\Auth\Services\MfaFactorService;

/**
 * Authentication & Token Lifecycle v2 HTTP API (additive).
 */
final class AuthLifecycleController extends ApiController
{
    public function status(): void
    {
        // Always available so clients can discover enabled/schema even when lifecycle is off.
        $this->ok(container(AuthLifecycleService::class)->status(), $this->platformMeta());
    }

    public function login(): void
    {
        container(AuthLifecycleService::class)->assertEnabled();
        $body = $this->jsonBody();
        $this->validator()->validate($body, [
            'email' => 'required|email|max:191',
            'password' => 'required|min:1|max:255',
            'device_name' => 'max:120',
            'device_fingerprint' => 'max:255',
            'platform' => 'max:80',
        ]);

        $bundle = container(AuthLifecycleService::class)->login(
            (string) ($body['email'] ?? ''),
            (string) ($body['password'] ?? ''),
            [
                'name' => (string) ($body['device_name'] ?? ''),
                'fingerprint' => (string) ($body['device_fingerprint'] ?? ''),
                'platform' => (string) ($body['platform'] ?? ''),
            ]
        );
        $this->ok($bundle, $this->platformMeta());
    }

    public function refresh(): void
    {
        container(AuthLifecycleService::class)->assertEnabled();
        $body = $this->jsonBody();
        $token = (string) ($body['refresh_token'] ?? '');
        if ($token === '') {
            throw ApiException::validation('refresh_token is required.', [
                'refresh_token' => ['refresh_token is required.'],
            ]);
        }
        $this->ok(container(AuthLifecycleService::class)->refresh($token), $this->platformMeta());
    }

    public function logout(): void
    {
        container(AuthLifecycleService::class)->assertEnabled();
        $body = $this->jsonBody();
        $userId = (int) ($this->actor()['id'] ?? 0);
        $this->ok(
            container(AuthLifecycleService::class)->logoutCurrent(
                isset($body['refresh_token']) ? (string) $body['refresh_token'] : null,
                $userId,
                ApiAuth::isAccessToken() ? ApiAuth::tokenId() : null
            ),
            $this->platformMeta()
        );
    }

    public function logoutEverywhere(): void
    {
        container(AuthLifecycleService::class)->assertEnabled();
        $userId = (int) ($this->actor()['id'] ?? 0);
        $body = $this->jsonBody();
        // Backward compatible: config default, optional request override.
        $revokePats = (bool) config('auth_lifecycle.logout_everywhere_revokes_pats', true);
        if (array_key_exists('revoke_pats', $body)) {
            $revokePats = filter_var($body['revoke_pats'], FILTER_VALIDATE_BOOLEAN);
        }
        $this->ok(
            container(AuthLifecycleService::class)->logoutEverywhere($userId, $revokePats),
            $this->platformMeta()
        );
    }

    public function devices(): void
    {
        container(AuthLifecycleService::class)->assertEnabled();
        $userId = (int) ($this->actor()['id'] ?? 0);
        $rows = container(DeviceSessionService::class)->listForUser($userId);
        $this->ok(['devices' => $rows], $this->platformMeta());
    }

    public function revokeDevice(string $device): void
    {
        container(AuthLifecycleService::class)->assertEnabled();
        $userId = (int) ($this->actor()['id'] ?? 0);
        $deviceId = (int) $device;
        if ($deviceId < 1) {
            throw ApiException::notFound('Device not found.');
        }
        $result = container(LogoutEverywhereService::class)->revokeDevice($userId, $deviceId);
        if (!($result['device_revoked'] ?? false)
            && ($result['refresh_revoked'] ?? 0) < 1
            && ($result['access_revoked'] ?? 0) < 1
        ) {
            throw ApiException::notFound('Device not found.');
        }
        $this->ok($result, $this->platformMeta());
    }

    public function forgotPassword(): void
    {
        container(AuthLifecycleService::class)->assertEnabled();
        $body = $this->jsonBody();
        $email = strtolower(trim((string) ($body['email'] ?? '')));
        if ($email === '') {
            throw ApiException::validation('Email is required.', ['email' => ['Email is required.']]);
        }
        $this->ok(container(AuthLifecycleService::class)->forgotPassword($email), $this->platformMeta());
    }

    public function resetPassword(): void
    {
        container(AuthLifecycleService::class)->assertEnabled();
        $body = $this->jsonBody();
        $this->ok(container(AuthLifecycleService::class)->resetPassword($body), $this->platformMeta());
    }

    public function verifyEmail(): void
    {
        container(AuthLifecycleService::class)->assertEnabled();
        $body = $this->jsonBody();
        $token = (string) ($body['token'] ?? '');
        if ($token === '') {
            throw ApiException::validation('Token is required.', ['token' => ['Token is required.']]);
        }
        $this->ok(container(AuthLifecycleService::class)->verifyEmail($token), $this->platformMeta());
    }

    public function resendVerification(): void
    {
        container(AuthLifecycleService::class)->assertEnabled();
        $body = $this->jsonBody();
        $email = strtolower(trim((string) ($body['email'] ?? '')));
        if ($email === '') {
            throw ApiException::validation('Email is required.', ['email' => ['Email is required.']]);
        }
        $this->ok(container(AuthLifecycleService::class)->resendVerification($email), $this->platformMeta());
    }

    public function mfaStatus(): void
    {
        container(AuthLifecycleService::class)->assertEnabled();
        $userId = (int) ($this->actor()['id'] ?? 0);
        $this->ok(container(MfaFactorService::class)->statusForUser($userId), $this->platformMeta());
    }

    public function mfaRegister(): void
    {
        container(AuthLifecycleService::class)->assertEnabled();
        $body = $this->jsonBody();
        $userId = (int) ($this->actor()['id'] ?? 0);
        $created = container(MfaFactorService::class)->registerPlaceholder(
            $userId,
            (string) ($body['type'] ?? 'totp'),
            (string) ($body['label'] ?? 'Authenticator')
        );
        $this->ok($created, $this->platformMeta(), 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function jsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if (!is_string($raw) || trim($raw) === '') {
            return is_array($_POST) ? $_POST : [];
        }
        $data = json_decode($raw, true);

        return is_array($data) ? $data : [];
    }
}

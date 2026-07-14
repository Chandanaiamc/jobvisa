<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Auth\Services;

use JobVisa\App\Auth\AuthManager;
use JobVisa\App\Auth\EmailVerificationService;
use JobVisa\App\Auth\PasswordHasher;
use JobVisa\App\Auth\PasswordResetService;
use JobVisa\App\Auth\UserRepository;
use JobVisa\App\Domain\Api\Auth\PersonalAccessTokenService;
use JobVisa\App\Domain\Api\Http\ApiException;
use JobVisa\App\Domain\Auth\Support\AuthTokenHasher;
use JobVisa\App\Domain\Auth\Support\AuthTokenLifecycleVersion;
use JobVisa\App\Domain\Security\Services\SecurityAuditLogger;
use JobVisa\App\Security\SecurityHelper;

/**
 * Password login → access PAT + refresh; refresh rotation; password/email API adapters.
 */
final class AuthLifecycleService
{
    public function __construct(
        private readonly AuthManager $auth,
        private readonly UserRepository $users,
        private readonly PasswordHasher $hasher,
        private readonly PersonalAccessTokenService $accessTokens,
        private readonly RefreshTokenService $refresh,
        private readonly DeviceSessionService $devices,
        private readonly LogoutEverywhereService $logoutEverywhere,
        private readonly MfaFactorService $mfa,
        private readonly AuthTokenHasher $tokenHasher,
        private readonly EmailVerificationService $emailVerification,
        private readonly PasswordResetService $passwordReset,
        private readonly SecurityAuditLogger $audit,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function status(): array
    {
        return [
            'status' => (bool) config('auth_lifecycle.enabled', true) ? 'ok' : 'disabled',
            'version' => AuthTokenLifecycleVersion::CURRENT,
            'enabled' => (bool) config('auth_lifecycle.enabled', true),
            'schema' => [
                'devices' => $this->devices->ensureSchemaReady(),
                'refresh' => $this->refresh->ensureSchemaReady(),
                'mfa' => $this->mfa->ensureSchemaReady(),
            ],
            'features' => [
                'refresh_rotation' => true,
                'refresh_family_tracking' => true,
                'device_sessions' => true,
                'multi_device' => true,
                'logout_everywhere' => true,
                'account_lockout' => true,
                'mfa_ready' => true,
                'email_verification' => true,
                'password_reset' => true,
                'pat_revocation' => true,
                'utc_expiry' => true,
                'app_key_hashing' => true,
                'transactional_refresh' => true,
                'device_revokes_access' => true,
            ],
            'access_ttl_seconds' => (int) config('auth_lifecycle.access_ttl_seconds', 3600),
            'refresh_ttl_days' => (int) config('auth_lifecycle.refresh_ttl_days', 30),
        ];
    }

    public function assertEnabled(): void
    {
        if (!(bool) config('auth_lifecycle.enabled', true)) {
            throw new ApiException(
                'auth_lifecycle_disabled',
                'Authentication lifecycle API is disabled.',
                503
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function login(string $email, string $password, array $device = []): array
    {
        $this->assertEnabled();
        $email = strtolower(trim($email));
        if ($this->auth->isThrottled($email)) {
            $this->audit->log('auth.login_locked', null, 'user', null, [], ['email' => $email]);
            throw new ApiException('account_locked', 'Too many login attempts. Please try again later.', 429);
        }

        if ($email === '' || $password === '') {
            $this->auth->loginAttempts()->record($email !== '' ? $email : null, false);
            throw ApiException::unauthorized('Invalid credentials.');
        }

        $user = $this->users->findActiveByEmail($email);
        if ($user === null) {
            $this->auth->loginAttempts()->record($email, false);
            $this->audit->log('auth.login_failed', null, 'user', null, [], ['reason' => 'unknown_user']);
            throw ApiException::unauthorized('Invalid credentials.');
        }

        if (($user['status'] ?? '') === 'suspended') {
            $this->auth->loginAttempts()->record($email, false);
            throw ApiException::forbidden('This account is suspended.');
        }

        if (!$this->hasher->verify($password, (string) ($user['password_hash'] ?? ''))) {
            $this->auth->loginAttempts()->record($email, false);
            $this->audit->log('auth.login_failed', (int) $user['id'], 'user', (int) $user['id'], [], [
                'reason' => 'bad_password',
            ]);
            throw ApiException::unauthorized('Invalid credentials.');
        }

        // Soft gate when MFA enforced and factors exist (challenge API reserved).
        if ((bool) config('auth_lifecycle.mfa_enforced', false) && $this->mfa->ensureSchemaReady()) {
            $mfa = $this->mfa->statusForUser((int) $user['id']);
            if (($mfa['has_enabled_factor'] ?? false) === true) {
                throw new ApiException(
                    'mfa_required',
                    'Multi-factor authentication is required. Challenge endpoint not enabled yet.',
                    401,
                    ['mfa_ready' => true, 'challenge_required' => true]
                );
            }
        }

        $this->auth->loginAttempts()->record($email, true);
        $this->users->touchLastLogin((int) $user['id']);
        unset($user['password_hash']);

        $bundle = $this->issueSessionBundle((int) $user['id'], $device, 'login');
        $this->audit->log('auth.login_success', (int) $user['id'], 'user', (int) $user['id'], [], [
            'device_id' => $bundle['device']['id'] ?? null,
            'ip' => SecurityHelper::clientIp(),
        ]);

        return array_merge($bundle, [
            'user' => [
                'id' => (int) $user['id'],
                'email' => (string) ($user['email'] ?? ''),
                'full_name' => (string) ($user['full_name'] ?? ''),
                'role' => (string) ($user['role'] ?? ''),
                'email_verified_at' => $user['email_verified_at'] ?? null,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function refresh(string $refreshToken): array
    {
        $this->assertEnabled();
        $rotated = $this->refresh->rotate($refreshToken);
        $userId = (int) $rotated['user_id'];
        $deviceId = $rotated['device_id'] ?? null;
        if (is_int($deviceId)) {
            $this->devices->touch($deviceId);
        }

        if (!empty($rotated['prior_access_token_id'])) {
            $this->accessTokens->revoke($userId, (int) $rotated['prior_access_token_id']);
        }

        $ttlSeconds = max(60, (int) config('auth_lifecycle.access_ttl_seconds', 3600));
        $ttlDays = max(1, (int) ceil($ttlSeconds / 86400));
        // Prefer sub-day TTL via custom create — PersonalAccessTokenService uses days; store precise UTC via create then update if needed.
        $access = $this->accessTokens->create(
            $userId,
            'access:' . ($deviceId ?? 'api'),
            $ttlDays
        );
        // Short-circuit expires_at to exact UTC seconds for access tokens from login/refresh.
        $preciseExpiry = $this->tokenHasher->utcPlusSeconds($ttlSeconds);
        \App\Core\Database::query(
            'UPDATE `api_personal_access_tokens` SET `expires_at` = :exp WHERE `id` = :id',
            ['exp' => $preciseExpiry, 'id' => (int) $access['id']]
        );
        $access['expires_at'] = $preciseExpiry;

        // Re-bind access id onto new refresh row
        \App\Core\Database::query(
            'UPDATE `auth_refresh_tokens` SET `access_token_id` = :aid WHERE `id` = :id',
            ['aid' => (int) $access['id'], 'id' => (int) $rotated['id']]
        );

        return [
            'token_type' => 'Bearer',
            'access_token' => $access['token'],
            'access_expires_at' => $preciseExpiry,
            'refresh_token' => $rotated['refresh_token'],
            'refresh_expires_at' => $rotated['expires_at'],
            'family_id' => $rotated['family_id'],
            'device_id' => $deviceId,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function logoutCurrent(?string $refreshToken, int $userId, ?int $accessTokenId): array
    {
        $this->assertEnabled();
        $refreshRevoked = 0;
        if (is_string($refreshToken) && $refreshToken !== '') {
            $refreshRevoked = $this->refresh->revokePresented($refreshToken, $userId);
        }
        if ($accessTokenId !== null && $accessTokenId > 0) {
            $this->accessTokens->revoke($userId, $accessTokenId);
        }
        $this->audit->log('auth.logout', $userId, 'user', $userId, [], [
            'refresh_revoked' => $refreshRevoked,
            'access_token_id' => $accessTokenId,
        ]);

        return ['logged_out' => true, 'refresh_revoked' => $refreshRevoked];
    }

    /**
     * @return array<string, mixed>
     */
    public function logoutEverywhere(int $userId, bool $revokePats = true): array
    {
        $this->assertEnabled();
        return $this->logoutEverywhere->revokeAll($userId, $revokePats);
    }

    /**
     * @return array<string, mixed>
     */
    public function verifyEmail(string $token): array
    {
        $this->assertEnabled();
        $result = $this->emailVerification->verify($token);
        $this->audit->log('auth.email_verified', isset($result['user_id']) ? (int) $result['user_id'] : null, 'user', null, [], [
            'success' => (bool) ($result['success'] ?? false),
        ]);
        if (!($result['success'] ?? false)) {
            throw ApiException::validation((string) ($result['message'] ?? 'Verification failed.'), [
                'token' => [(string) ($result['message'] ?? 'Invalid or expired token.')],
            ]);
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function resendVerification(string $email): array
    {
        $this->assertEnabled();
        $result = $this->emailVerification->resend($email);
        $this->audit->log('auth.email_verification_resent', null, 'user', null, [], [
            'success' => (bool) ($result['success'] ?? false),
        ]);

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function forgotPassword(string $email): array
    {
        $this->assertEnabled();
        $result = $this->passwordReset->request($email);
        $this->audit->log('auth.password_reset_requested', null, 'user', null, [], [
            'success' => (bool) ($result['success'] ?? false),
        ]);

        return $result;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function resetPassword(array $payload): array
    {
        $this->assertEnabled();
        $result = $this->passwordReset->reset($payload);
        if (!($result['success'] ?? false)) {
            throw ApiException::validation((string) ($result['message'] ?? 'Reset failed.'), $result['errors'] ?? []);
        }
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $user = $email !== '' ? $this->users->findActiveByEmail($email) : null;
        $userId = $user !== null ? (int) $user['id'] : null;
        if ($userId !== null && $userId > 0) {
            $this->logoutEverywhere->revokeAll($userId, true);
        }
        $this->audit->log('auth.password_reset_completed', $userId, 'user', $userId);

        return $result;
    }

    /**
     * @param  array<string, mixed>  $device
     * @return array<string, mixed>
     */
    private function issueSessionBundle(int $userId, array $device, string $reason): array
    {
        if (!$this->refresh->ensureSchemaReady() || !$this->devices->ensureSchemaReady()) {
            throw new ApiException('misconfigured', 'Auth lifecycle schema is not installed. Apply migration 065.', 503);
        }

        $deviceRow = $this->devices->touchOrCreate(
            $userId,
            isset($device['fingerprint']) ? (string) $device['fingerprint'] : null,
            isset($device['name']) ? (string) $device['name'] : null,
            isset($device['platform']) ? (string) $device['platform'] : null,
        );

        $ttlSeconds = max(60, (int) config('auth_lifecycle.access_ttl_seconds', 3600));
        $ttlDays = max(1, (int) ceil($ttlSeconds / 86400));
        $access = $this->accessTokens->create($userId, 'access:' . $deviceRow['id'] . ':' . $reason, $ttlDays);
        $preciseExpiry = $this->tokenHasher->utcPlusSeconds($ttlSeconds);
        \App\Core\Database::query(
            'UPDATE `api_personal_access_tokens` SET `expires_at` = :exp WHERE `id` = :id',
            ['exp' => $preciseExpiry, 'id' => (int) $access['id']]
        );
        $access['expires_at'] = $preciseExpiry;

        $familyId = $this->tokenHasher->familyId();
        $refresh = $this->refresh->issue($userId, (int) $deviceRow['id'], $familyId, (int) $access['id']);

        return [
            'token_type' => 'Bearer',
            'access_token' => $access['token'],
            'access_expires_at' => $preciseExpiry,
            'refresh_token' => $refresh['refresh_token'],
            'refresh_expires_at' => $refresh['expires_at'],
            'family_id' => $familyId,
            'device' => [
                'id' => (int) $deviceRow['id'],
                'name' => (string) $deviceRow['name'],
            ],
        ];
    }
}

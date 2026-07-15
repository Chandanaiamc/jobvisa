<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Auth\Services;

use JobVisa\App\Auth\UserRepository;
use JobVisa\App\Domain\Api\Auth\ApiAuth;
use JobVisa\App\Domain\Api\Http\ApiException;
use JobVisa\App\Domain\Auth\Repositories\AccessTokenRepository;
use JobVisa\App\Domain\Auth\Support\AuthTokenHasher;
use JobVisa\App\Security\SecurityHelper;

/**
 * Short-lived session access tokens (not PATs; not renewable except via refresh).
 */
final class AccessTokenService
{
    public function __construct(
        private readonly AccessTokenRepository $tokens,
        private readonly AuthTokenHasher $hasher,
        private readonly UserRepository $users,
    ) {
    }

    public function ensureSchemaReady(): bool
    {
        return $this->tokens->ensureSchemaReady();
    }

    /**
     * @return array{token: string, id: int, expires_at: string, prefix: string, name: string}
     */
    public function issue(int $userId, ?int $deviceId, string $name = 'access'): array
    {
        if (!$this->tokens->ensureSchemaReady()) {
            throw new ApiException(
                'misconfigured',
                'Access token schema is not installed. Apply migration 066.',
                503
            );
        }

        $user = $this->users->findActiveById($userId);
        if ($user === null) {
            throw ApiException::forbidden('User not found or inactive.');
        }

        $ttl = max(60, (int) config('auth_lifecycle.access_ttl_seconds', 900));
        $prefix = (string) config('auth_lifecycle.access_prefix', 'jva1_');
        $bytes = (int) config('api.token_bytes', 32);
        $plain = $prefix . bin2hex(random_bytes($bytes));
        $expires = $this->hasher->utcPlusSeconds($ttl);
        $name = trim($name) !== '' ? mb_substr(trim($name), 0, 120) : 'access';

        $id = $this->tokens->create([
            'user_id' => $userId,
            'device_id' => $deviceId,
            'token_hash' => $this->hasher->hash($plain),
            'token_prefix' => mb_substr($plain, 0, 12),
            'name' => $name,
            'expires_at' => $expires,
        ]);

        return [
            'token' => $plain,
            'id' => $id,
            'expires_at' => $expires,
            'prefix' => mb_substr($plain, 0, 12),
            'name' => $name,
        ];
    }

    public function revoke(int $userId, int $tokenId): bool
    {
        return $this->tokens->revoke($tokenId, $userId);
    }

    public function revokeAllForUser(int $userId): int
    {
        return $this->tokens->revokeAllForUser($userId);
    }

    public function revokeForDevice(int $deviceId, int $userId): int
    {
        return $this->tokens->revokeForDevice($deviceId, $userId);
    }

    /**
     * @param  list<int>  $ids
     */
    public function revokeIds(array $ids, int $userId): int
    {
        return $this->tokens->revokeIds($ids, $userId);
    }

    /**
     * Authenticate a plaintext session access token and set ApiAuth.
     */
    public function authenticatePlain(string $plain): void
    {
        if (!$this->tokens->ensureSchemaReady()) {
            throw ApiException::unauthorized('API authentication unavailable.');
        }

        $row = $this->tokens->findByHash($this->hasher->hash($plain));
        if ($row === null) {
            throw ApiException::unauthorized('Invalid access token.');
        }

        if (!empty($row['revoked_at'])) {
            throw ApiException::tokenRevoked();
        }

        if ($this->hasher->isExpiredUtc($row['expires_at'] ?? null)) {
            throw ApiException::tokenExpired();
        }

        $user = $this->users->findActiveById((int) $row['user_id']);
        if ($user === null) {
            throw ApiException::unauthorized('Token user is inactive.');
        }

        unset($user['password_hash']);

        $ip = SecurityHelper::clientIp();
        $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $this->tokens->touchUsage((int) $row['id'], $ip, $ua);

        ApiAuth::login($user, [
            'id' => (int) $row['id'],
            'name' => (string) ($row['name'] ?? 'access'),
            'prefix' => (string) ($row['token_prefix'] ?? ''),
            'kind' => 'access',
            'device_id' => isset($row['device_id']) ? (int) $row['device_id'] : null,
        ]);
    }
}

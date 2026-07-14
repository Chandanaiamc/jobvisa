<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Auth\Services;

use App\Core\Database;
use JobVisa\App\Domain\Api\Http\ApiException;
use JobVisa\App\Domain\Auth\Repositories\RefreshTokenRepository;
use JobVisa\App\Domain\Auth\Support\AuthTokenHasher;
use JobVisa\App\Domain\Security\Services\SecurityAuditLogger;
use JobVisa\App\Security\SecurityHelper;
use Throwable;

/**
 * Refresh token issuance, rotation, and family reuse detection.
 */
final class RefreshTokenService
{
    public function __construct(
        private readonly RefreshTokenRepository $tokens,
        private readonly AuthTokenHasher $hasher,
        private readonly SecurityAuditLogger $audit,
    ) {
    }

    public function ensureSchemaReady(): bool
    {
        return $this->tokens->ensureSchemaReady();
    }

    /**
     * @return array{refresh_token: string, id: int, family_id: string, expires_at: string}
     */
    public function issue(
        int $userId,
        ?int $deviceId,
        string $familyId,
        ?int $accessTokenId,
    ): array {
        $ttlDays = max(1, (int) config('auth_lifecycle.refresh_ttl_days', 30));
        $prefix = (string) config('auth_lifecycle.refresh_prefix', 'jvr1_');
        $plain = $prefix . bin2hex(random_bytes(32));
        $hash = $this->hasher->hash($plain);
        $expires = $this->hasher->utcPlusDays($ttlDays);

        $id = $this->tokens->create([
            'user_id' => $userId,
            'device_id' => $deviceId,
            'family_id' => $familyId,
            'token_hash' => $hash,
            'token_prefix' => mb_substr($plain, 0, 12),
            'access_token_id' => $accessTokenId,
            'expires_at' => $expires,
        ]);

        return [
            'refresh_token' => $plain,
            'id' => $id,
            'family_id' => $familyId,
            'expires_at' => $expires,
        ];
    }

    /**
     * Rotate refresh token inside a DB transaction (SELECT … FOR UPDATE).
     * Reuse of a rotated/revoked token revokes the whole family.
     *
     * @return array{refresh_token: string, id: int, family_id: string, expires_at: string, user_id: int, device_id: ?int, access_token_id: ?int, prior_access_token_id: ?int}
     */
    public function rotate(string $plainRefresh): array
    {
        if (!$this->tokens->ensureSchemaReady()) {
            throw new ApiException('misconfigured', 'Refresh token schema is not installed.', 503);
        }

        $pdo = Database::connection();
        $startedTx = false;
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $startedTx = true;
        }

        try {
            $row = $this->tokens->findByHashForUpdate($this->hasher->hash($plainRefresh));
            if ($row === null) {
                throw ApiException::unauthorized('Invalid refresh token.');
            }

            $familyId = (string) $row['family_id'];
            $userId = (int) $row['user_id'];

            if (!empty($row['revoked_at']) || !empty($row['rotated_at'])) {
                $this->tokens->revokeFamily($familyId);
                if ($startedTx && $pdo->inTransaction()) {
                    $pdo->commit();
                    $startedTx = false;
                }
                $this->audit->log('auth.refresh_reuse_detected', $userId, 'auth_refresh_family', null, [], [
                    'family_id' => $familyId,
                ]);
                throw ApiException::unauthorized('Refresh token reuse detected. Session family revoked.');
            }

            if ($this->hasher->isExpiredUtc($row['expires_at'] ?? null)) {
                $this->tokens->revokeFamily($familyId);
                if ($startedTx && $pdo->inTransaction()) {
                    $pdo->commit();
                    $startedTx = false;
                }
                throw ApiException::unauthorized('Refresh token has expired.');
            }

            $ip = SecurityHelper::clientIp();
            $this->tokens->touch((int) $row['id'], $ip);

            $issued = $this->issue(
                $userId,
                isset($row['device_id']) ? (int) $row['device_id'] : null,
                $familyId,
                null
            );
            $this->tokens->markRotated((int) $row['id'], (int) $issued['id']);

            if ($startedTx && $pdo->inTransaction()) {
                $pdo->commit();
                $startedTx = false;
            }

            $this->audit->log('auth.refresh_rotated', $userId, 'auth_refresh_token', (int) $issued['id'], [], [
                'family_id' => $familyId,
                'prior_id' => (int) $row['id'],
            ]);

            return array_merge($issued, [
                'user_id' => $userId,
                'device_id' => isset($row['device_id']) ? (int) $row['device_id'] : null,
                'access_token_id' => null,
                'prior_access_token_id' => isset($row['access_token_id']) ? (int) $row['access_token_id'] : null,
            ]);
        } catch (Throwable $e) {
            if ($startedTx && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function revokePresented(string $plainRefresh, ?int $actorUserId = null): int
    {
        $row = $this->tokens->findByHash($this->hasher->hash($plainRefresh));
        if ($row === null) {
            return 0;
        }

        return $this->revokeFamily((string) $row['family_id'], $actorUserId ?? (int) $row['user_id']);
    }

    public function revokeFamily(string $familyId, ?int $actorUserId = null): int
    {
        $n = $this->tokens->revokeFamily($familyId);
        $this->audit->log('auth.refresh_family_revoked', $actorUserId, 'auth_refresh_family', null, [], [
            'family_id' => $familyId,
            'count' => $n,
        ]);

        return $n;
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
     * @return list<int>
     */
    public function accessTokenIdsForDevice(int $deviceId, int $userId): array
    {
        return $this->tokens->listAccessTokenIdsForDevice($deviceId, $userId);
    }
}

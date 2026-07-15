<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Auth\Services;

use JobVisa\App\Domain\Api\Auth\PersonalAccessTokenRepository;
use JobVisa\App\Domain\Security\Services\SecurityAuditLogger;

/**
 * Secure logout everywhere: refresh families, devices, access tokens, and optional PATs.
 */
final class LogoutEverywhereService
{
    public function __construct(
        private readonly RefreshTokenService $refresh,
        private readonly DeviceSessionService $devices,
        private readonly AccessTokenService $accessTokens,
        private readonly PersonalAccessTokenRepository $pats,
        private readonly SecurityAuditLogger $audit,
    ) {
    }

    /**
     * @return array{refresh_revoked: int, devices_revoked: int, access_revoked: int, pats_revoked: int}
     */
    public function revokeAll(int $userId, bool $includePersonalAccessTokens = true): array
    {
        $refreshN = $this->refresh->revokeAllForUser($userId);
        $devicesN = $this->devices->revokeAll($userId);
        $accessN = $this->accessTokens->revokeAllForUser($userId);
        // Legacy Phase-1 session rows still stored as PATs named access:*
        $accessN += $this->revokeLegacyAccessNamedPats($userId);
        $patN = 0;
        if ($includePersonalAccessTokens) {
            $patN = $this->revokeAllPats($userId);
        }

        $this->audit->log('auth.logout_everywhere', $userId, 'user', $userId, [], [
            'refresh_revoked' => $refreshN,
            'devices_revoked' => $devicesN,
            'access_revoked' => $accessN,
            'pats_revoked' => $patN,
        ]);

        return [
            'refresh_revoked' => $refreshN,
            'devices_revoked' => $devicesN,
            'access_revoked' => $accessN,
            'pats_revoked' => $patN,
        ];
    }

    /**
     * Revoke a device session, its refresh tokens, and linked access tokens.
     * Long-lived PATs are not revoked.
     *
     * @return array{device_revoked: bool, refresh_revoked: int, access_revoked: int}
     */
    public function revokeDevice(int $userId, int $deviceId): array
    {
        $linkedIds = $this->refresh->accessTokenIdsForDevice($deviceId, $userId);
        $refreshN = $this->refresh->revokeForDevice($deviceId, $userId);
        $ok = $this->devices->revoke($deviceId, $userId);

        $accessRevoked = $this->accessTokens->revokeIds($linkedIds, $userId);
        $accessRevoked += $this->accessTokens->revokeForDevice($deviceId, $userId);
        $accessRevoked += $this->revokeLegacyAccessNamedPats($userId, $deviceId);

        $this->audit->log('auth.device_logout', $userId, 'auth_device', $deviceId, [], [
            'refresh_revoked' => $refreshN,
            'access_revoked' => $accessRevoked,
        ]);

        return [
            'device_revoked' => $ok,
            'refresh_revoked' => $refreshN,
            'access_revoked' => $accessRevoked,
        ];
    }

    /**
     * Revoke long-lived PATs only (does not touch auth_access_tokens).
     */
    public function revokeAllPats(int $userId): int
    {
        $list = $this->pats->listForUser($userId);
        $n = 0;
        foreach ($list as $row) {
            if (!empty($row['revoked_at'])) {
                continue;
            }
            $name = (string) ($row['name'] ?? '');
            // Leave legacy Phase-1 access:* rows for dedicated access revoke paths.
            if (str_starts_with($name, 'access:')) {
                continue;
            }
            if ($this->pats->revoke((int) $row['id'], $userId)) {
                $n++;
            }
        }

        return $n;
    }

    /**
     * Revoke legacy PAT-table rows named access:{deviceId} or access:{deviceId}:*.
     */
    private function revokeLegacyAccessNamedPats(int $userId, ?int $deviceId = null): int
    {
        $list = $this->pats->listForUser($userId);
        $n = 0;
        foreach ($list as $row) {
            if (!empty($row['revoked_at'])) {
                continue;
            }
            $name = (string) ($row['name'] ?? '');
            if ($deviceId !== null) {
                $exact = 'access:' . $deviceId;
                $prefix = 'access:' . $deviceId . ':';
                if ($name !== $exact && !str_starts_with($name, $prefix)) {
                    continue;
                }
            } elseif (!str_starts_with($name, 'access:')) {
                continue;
            }
            if ($this->pats->revoke((int) $row['id'], $userId)) {
                $n++;
            }
        }

        return $n;
    }
}

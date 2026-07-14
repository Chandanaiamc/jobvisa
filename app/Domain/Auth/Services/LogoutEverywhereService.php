<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Auth\Services;

use JobVisa\App\Domain\Api\Auth\PersonalAccessTokenRepository;
use JobVisa\App\Domain\Security\Services\SecurityAuditLogger;

/**
 * Secure logout everywhere: refresh families, devices, and PATs.
 */
final class LogoutEverywhereService
{
    public function __construct(
        private readonly RefreshTokenService $refresh,
        private readonly DeviceSessionService $devices,
        private readonly PersonalAccessTokenRepository $pats,
        private readonly SecurityAuditLogger $audit,
    ) {
    }

    /**
     * @return array{refresh_revoked: int, devices_revoked: int, pats_revoked: int}
     */
    public function revokeAll(int $userId, bool $includePersonalAccessTokens = true): array
    {
        $refreshN = $this->refresh->revokeAllForUser($userId);
        $devicesN = $this->devices->revokeAll($userId);
        $patN = 0;
        if ($includePersonalAccessTokens) {
            $patN = $this->revokeAllPats($userId);
        }

        $this->audit->log('auth.logout_everywhere', $userId, 'user', $userId, [], [
            'refresh_revoked' => $refreshN,
            'devices_revoked' => $devicesN,
            'pats_revoked' => $patN,
        ]);

        return [
            'refresh_revoked' => $refreshN,
            'devices_revoked' => $devicesN,
            'pats_revoked' => $patN,
        ];
    }

    /**
     * Revoke a device session, its refresh tokens, and linked access PATs.
     *
     * @return array{device_revoked: bool, refresh_revoked: int, access_revoked: int}
     */
    public function revokeDevice(int $userId, int $deviceId): array
    {
        $accessIds = $this->refresh->accessTokenIdsForDevice($deviceId, $userId);
        $refreshN = $this->refresh->revokeForDevice($deviceId, $userId);
        $ok = $this->devices->revoke($deviceId, $userId);

        $accessRevoked = 0;
        foreach ($accessIds as $accessId) {
            if ($this->pats->revoke($accessId, $userId)) {
                $accessRevoked++;
            }
        }
        // Also revoke session access tokens minted for this device by name prefix.
        $accessRevoked += $this->revokeAccessPatsByDevicePrefix($userId, $deviceId);

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

    public function revokeAllPats(int $userId): int
    {
        $list = $this->pats->listForUser($userId);
        $n = 0;
        foreach ($list as $row) {
            if (!empty($row['revoked_at'])) {
                continue;
            }
            if ($this->pats->revoke((int) $row['id'], $userId)) {
                $n++;
            }
        }

        return $n;
    }

    /**
     * Revoke PATs named access:{deviceId} or access:{deviceId}:* (login + refresh mint names).
     */
    private function revokeAccessPatsByDevicePrefix(int $userId, int $deviceId): int
    {
        $exact = 'access:' . $deviceId;
        $prefix = 'access:' . $deviceId . ':';
        $list = $this->pats->listForUser($userId);
        $n = 0;
        foreach ($list as $row) {
            if (!empty($row['revoked_at'])) {
                continue;
            }
            $name = (string) ($row['name'] ?? '');
            if ($name !== $exact && !str_starts_with($name, $prefix)) {
                continue;
            }
            if ($this->pats->revoke((int) $row['id'], $userId)) {
                $n++;
            }
        }

        return $n;
    }
}

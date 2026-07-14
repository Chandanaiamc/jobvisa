<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Auth\Services;

use JobVisa\App\Domain\Auth\Repositories\DeviceSessionRepository;
use JobVisa\App\Domain\Auth\Support\AuthTokenHasher;
use JobVisa\App\Domain\Security\Services\SecurityAuditLogger;
use JobVisa\App\Security\SecurityHelper;

/**
 * Device-based session registry for multi-device login.
 */
final class DeviceSessionService
{
    public function __construct(
        private readonly DeviceSessionRepository $devices,
        private readonly AuthTokenHasher $hasher,
        private readonly SecurityAuditLogger $audit,
    ) {
    }

    public function ensureSchemaReady(): bool
    {
        return $this->devices->ensureSchemaReady();
    }

    /**
     * @return array{id: int, fingerprint_hash: string, name: string}
     */
    public function touchOrCreate(int $userId, ?string $fingerprint, ?string $name, ?string $platform): array
    {
        $raw = trim((string) $fingerprint);
        if ($raw === '') {
            $raw = hash('sha256', SecurityHelper::clientIp() . '|' . (string) ($_SERVER['HTTP_USER_AGENT'] ?? '') . '|' . $userId);
        }
        $fpHash = $this->hasher->hash('device:' . $raw);
        $deviceName = trim((string) $name);
        if ($deviceName === '') {
            $deviceName = 'Device ' . mb_substr($fpHash, 0, 8);
        }

        $id = $this->devices->upsert([
            'user_id' => $userId,
            'fingerprint_hash' => $fpHash,
            'name' => mb_substr($deviceName, 0, 120),
            'platform' => $platform !== null ? mb_substr($platform, 0, 80) : null,
            'last_ip' => SecurityHelper::clientIp(),
            'last_user_agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 512),
        ]);

        return [
            'id' => $id,
            'fingerprint_hash' => $fpHash,
            'name' => $deviceName,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForUser(int $userId): array
    {
        return $this->devices->listForUser($userId);
    }

    public function revoke(int $deviceId, int $userId): bool
    {
        $ok = $this->devices->revoke($deviceId, $userId);
        if ($ok) {
            $this->audit->log('auth.device_revoked', $userId, 'auth_device', $deviceId);
        }

        return $ok;
    }

    public function revokeAll(int $userId, ?int $exceptDeviceId = null): int
    {
        $n = $this->devices->revokeAllForUser($userId, $exceptDeviceId);
        $this->audit->log('auth.devices_revoked_all', $userId, 'auth_device', null, [], [
            'count' => $n,
            'except' => $exceptDeviceId,
        ]);

        return $n;
    }

    public function findForUser(int $deviceId, int $userId): ?array
    {
        return $this->devices->findForUser($deviceId, $userId);
    }

    public function touch(int $deviceId): void
    {
        $this->devices->touch(
            $deviceId,
            SecurityHelper::clientIp(),
            (string) ($_SERVER['HTTP_USER_AGENT'] ?? '')
        );
    }
}

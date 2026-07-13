<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Security\Services;

use App\Core\Database;
use JobVisa\App\Logging\Logger;
use JobVisa\App\Security\SecurityHelper;

/**
 * Writes security events to audit_logs (and Logger::security). Fail-safe.
 */
final class SecurityAuditLogger
{
    public function ensureSchemaReady(): bool
    {
        try {
            Database::query('SELECT 1 FROM audit_logs LIMIT 1');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function log(
        string $action,
        ?int $actorUserId = null,
        ?string $entityType = null,
        ?int $entityId = null,
        array $before = [],
        array $after = [],
    ): void {
        Logger::security($action, [
            'actor_user_id' => $actorUserId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ]);

        if (!(bool) config('security.audit_enabled', true) || !$this->ensureSchemaReady()) {
            return;
        }

        try {
            $beforeJson = $before === [] ? null : json_encode($before, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $afterJson = $after === [] ? null : json_encode($after, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            Database::query(
                'INSERT INTO `audit_logs`
                    (`actor_user_id`, `action`, `entity_type`, `entity_id`, `ip_address`, `user_agent`,
                     `before_json`, `after_json`, `created_at`, `updated_at`)
                 VALUES
                    (:actor, :action, :entity_type, :entity_id, :ip, :ua,
                     :before_json, :after_json, CURRENT_TIMESTAMP(3), CURRENT_TIMESTAMP(3))',
                [
                    'actor' => $actorUserId,
                    'action' => mb_substr($action, 0, 100),
                    'entity_type' => $entityType !== null ? mb_substr($entityType, 0, 80) : null,
                    'entity_id' => $entityId,
                    'ip' => mb_substr(SecurityHelper::clientIp(), 0, 45),
                    'ua' => SecurityHelper::userAgent(512),
                    'before_json' => $beforeJson,
                    'after_json' => $afterJson,
                ]
            );
        } catch (\Throwable) {
            // Never break the request path.
        }
    }
}

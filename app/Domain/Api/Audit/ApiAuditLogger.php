<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Api\Audit;

use App\Core\Database;
use JobVisa\App\Domain\Api\Auth\ApiAuth;
use JobVisa\App\Domain\Observability\Services\RequestContext;
use JobVisa\App\Security\SecurityHelper;

/**
 * Append-only API audit trail (no secrets / payloads).
 */
final class ApiAuditLogger
{
    public function ensureSchemaReady(): bool
    {
        try {
            Database::query('SELECT 1 FROM api_audit_logs LIMIT 1');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function log(
        string $method,
        string $endpoint,
        int $status,
        float $durationMs,
        int $responseSize = 0,
    ): void {
        if (!(bool) config('api.audit_enabled', true) || !$this->ensureSchemaReady()) {
            return;
        }

        try {
            Database::query(
                'INSERT INTO `api_audit_logs`
                    (`request_id`, `endpoint`, `method`, `user_id`, `token_id`, `ip`, `user_agent`,
                     `status_code`, `duration_ms`, `response_size`, `created_at`)
                 VALUES
                    (:request_id, :endpoint, :method, :user_id, :token_id, :ip, :ua,
                     :status_code, :duration_ms, :response_size, CURRENT_TIMESTAMP(3))',
                [
                    'request_id' => mb_substr((string) (RequestContext::currentId() ?? ''), 0, 64),
                    'endpoint' => mb_substr($endpoint, 0, 255),
                    'method' => mb_substr(strtoupper($method), 0, 10),
                    'user_id' => ApiAuth::id(),
                    'token_id' => ApiAuth::tokenId(),
                    'ip' => mb_substr(SecurityHelper::clientIp(), 0, 45),
                    'ua' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 512),
                    'status_code' => $status,
                    'duration_ms' => round($durationMs, 2),
                    'response_size' => max(0, $responseSize),
                ]
            );
        } catch (\Throwable) {
            // Never break the request path.
        }
    }
}

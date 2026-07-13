<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Api\Webhooks;

use App\Core\Database;
use PDO;

/**
 * Webhook subscription + delivery log foundation (disabled until configured).
 */
final class WebhookRepository
{
    public function ensureSchemaReady(): bool
    {
        try {
            Database::query('SELECT 1 FROM api_webhook_subscriptions LIMIT 1');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function activeForEvent(string $event): array
    {
        if (!$this->ensureSchemaReady()) {
            return [];
        }
        $rows = Database::query(
            'SELECT * FROM `api_webhook_subscriptions`
             WHERE `enabled` = 1 AND `event` = :event
             ORDER BY `id` ASC',
            ['event' => $event]
        )->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function logDelivery(array $data): void
    {
        if (!$this->ensureSchemaReady()) {
            return;
        }
        Database::query(
            'INSERT INTO `api_webhook_deliveries`
                (`subscription_id`, `event`, `payload_hash`, `status`, `attempt`, `next_retry_at`,
                 `response_code`, `error_message`, `created_at`)
             VALUES
                (:subscription_id, :event, :payload_hash, :status, :attempt, :next_retry_at,
                 :response_code, :error_message, CURRENT_TIMESTAMP(3))',
            [
                'subscription_id' => (int) $data['subscription_id'],
                'event' => (string) $data['event'],
                'payload_hash' => (string) $data['payload_hash'],
                'status' => (string) $data['status'],
                'attempt' => (int) ($data['attempt'] ?? 1),
                'next_retry_at' => $data['next_retry_at'] ?? null,
                'response_code' => $data['response_code'] ?? null,
                'error_message' => $data['error_message'] ?? null,
            ]
        );
    }
}

-- =============================================================================
-- Migration : 064_create_api_platform_tables
-- Project   : JobVisa.lk Enterprise
-- Purpose   : Sprint 4.5 — Personal access tokens, API audit, webhooks foundation
-- Preserves : no destructive changes to prior tables
-- =============================================================================

CREATE TABLE IF NOT EXISTS `api_personal_access_tokens` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(120) NOT NULL,
    `token_hash` CHAR(64) NOT NULL,
    `token_prefix` VARCHAR(16) NOT NULL DEFAULT '',
    `abilities` JSON NULL,
    `last_used_at` DATETIME(3) NULL DEFAULT NULL,
    `last_used_ip` VARCHAR(45) NULL DEFAULT NULL,
    `last_used_user_agent` VARCHAR(512) NULL DEFAULT NULL,
    `expires_at` DATETIME(3) NULL DEFAULT NULL,
    `revoked_at` DATETIME(3) NULL DEFAULT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_api_pat_hash` (`token_hash`),
    KEY `idx_api_pat_user` (`user_id`, `revoked_at`),
    CONSTRAINT `fk_api_pat_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `api_audit_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `request_id` VARCHAR(64) NOT NULL DEFAULT '',
    `endpoint` VARCHAR(255) NOT NULL,
    `method` VARCHAR(10) NOT NULL,
    `user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `token_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `ip` VARCHAR(45) NOT NULL DEFAULT '',
    `user_agent` VARCHAR(512) NOT NULL DEFAULT '',
    `status_code` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `duration_ms` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `response_size` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    KEY `idx_api_audit_created` (`created_at`),
    KEY `idx_api_audit_user` (`user_id`, `created_at`),
    KEY `idx_api_audit_request` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `api_webhook_subscriptions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `event` VARCHAR(64) NOT NULL,
    `url` VARCHAR(500) NOT NULL,
    `secret` VARCHAR(128) NOT NULL,
    `enabled` TINYINT(1) NOT NULL DEFAULT 0,
    `description` VARCHAR(255) NULL DEFAULT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    KEY `idx_api_wh_event_enabled` (`event`, `enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `api_webhook_deliveries` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `subscription_id` BIGINT UNSIGNED NOT NULL,
    `event` VARCHAR(64) NOT NULL,
    `payload_hash` CHAR(64) NOT NULL,
    `status` VARCHAR(32) NOT NULL DEFAULT 'pending',
    `attempt` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `next_retry_at` DATETIME(3) NULL DEFAULT NULL,
    `response_code` SMALLINT UNSIGNED NULL DEFAULT NULL,
    `error_message` VARCHAR(255) NULL DEFAULT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    KEY `idx_api_whd_sub` (`subscription_id`, `created_at`),
    KEY `idx_api_whd_retry` (`status`, `next_retry_at`),
    CONSTRAINT `fk_api_whd_sub`
        FOREIGN KEY (`subscription_id`) REFERENCES `api_webhook_subscriptions` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

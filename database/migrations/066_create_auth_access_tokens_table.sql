-- =============================================================================
-- Migration : 066_create_auth_access_tokens_table
-- Project   : JobVisa.lk Enterprise
-- Purpose   : Auth Token Lifecycle v2 Phase 2 — short-lived access tokens
--             (separated from long-lived api_personal_access_tokens PATs)
-- Preserves : additive; no changes to PAT table
-- =============================================================================

CREATE TABLE IF NOT EXISTS `auth_access_tokens` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `device_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `token_hash` CHAR(64) NOT NULL,
    `token_prefix` VARCHAR(16) NOT NULL DEFAULT '',
    `name` VARCHAR(120) NOT NULL DEFAULT 'access',
    `last_used_at` DATETIME(3) NULL DEFAULT NULL,
    `last_used_ip` VARCHAR(45) NULL DEFAULT NULL,
    `last_used_user_agent` VARCHAR(512) NULL DEFAULT NULL,
    `expires_at` DATETIME(3) NOT NULL,
    `revoked_at` DATETIME(3) NULL DEFAULT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_auth_access_hash` (`token_hash`),
    KEY `idx_auth_access_user` (`user_id`, `revoked_at`),
    KEY `idx_auth_access_device` (`device_id`, `revoked_at`),
    KEY `idx_auth_access_expires` (`expires_at`),
    CONSTRAINT `fk_auth_access_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

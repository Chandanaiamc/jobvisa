-- =============================================================================
-- Migration : 030_create_email_verification_tokens_table
-- Project   : JobVisa.lk Enterprise
-- Target    : MariaDB 10.4+
-- Purpose   : Email verification tokens (store hashes only in token_hash)
-- Depends   : 008_create_users_table
-- =============================================================================

CREATE TABLE IF NOT EXISTS `email_verification_tokens` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `token_hash` VARCHAR(255) NOT NULL,
    `expires_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `verified_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_email_verification_tokens_user_id` (`user_id`),
    KEY `idx_email_verification_tokens_token_hash` (`token_hash`(191)),
    KEY `idx_email_verification_tokens_expires_at` (`expires_at`),
    CONSTRAINT `fk_email_verification_tokens_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

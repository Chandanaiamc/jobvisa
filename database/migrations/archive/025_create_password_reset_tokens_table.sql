-- =============================================================================
-- Migration : 025_create_password_reset_tokens_table
-- Project   : JobVisa.lk
-- Target    : MariaDB 10.4+ compatible
-- Purpose   : Store hashed password-reset tokens with expiry
-- Security  : Persist only token HASH (e.g. SHA-256 hex), never raw tokens
-- =============================================================================

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(191) NOT NULL,
    `user_id` BIGINT UNSIGNED DEFAULT NULL,
    `token` CHAR(64) NOT NULL COMMENT 'SHA-256 hex hash of raw token',
    `expires_at` DATETIME(3) NOT NULL,
    `used_at` DATETIME(3) DEFAULT NULL,
    `requested_ip` VARCHAR(45) DEFAULT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_password_reset_tokens_token` (`token`),
    KEY `idx_password_reset_tokens_email` (`email`),
    KEY `idx_password_reset_tokens_user_id` (`user_id`),
    KEY `idx_password_reset_tokens_expires_at` (`expires_at`),
    KEY `idx_password_reset_tokens_email_expires` (`email`, `expires_at`),
    CONSTRAINT `fk_password_reset_tokens_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

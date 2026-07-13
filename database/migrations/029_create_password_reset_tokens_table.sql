-- =============================================================================
-- Migration : 029_create_password_reset_tokens_table
-- Project   : JobVisa.lk Enterprise
-- Target    : MariaDB 10.4+
-- Purpose   : Password reset tokens (store hashes only in token_hash)
-- Notes     : If legacy 025 table exists with `token`, rename to token_hash.
-- =============================================================================

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NULL,
    `email` VARCHAR(191) NOT NULL,
    `token_hash` VARCHAR(255) NOT NULL,
    `expires_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `used_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_password_reset_tokens_user_id` (`user_id`),
    KEY `idx_password_reset_tokens_email` (`email`),
    KEY `idx_password_reset_tokens_token_hash` (`token_hash`(191)),
    KEY `idx_password_reset_tokens_expires_at` (`expires_at`),
    CONSTRAINT `fk_password_reset_tokens_auth_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Legacy alignment: 025 used column `token` CHAR(64)
SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'password_reset_tokens'
              AND COLUMN_NAME = 'token'
        ) AND NOT EXISTS(
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'password_reset_tokens'
              AND COLUMN_NAME = 'token_hash'
        ),
        'ALTER TABLE `password_reset_tokens` CHANGE COLUMN `token` `token_hash` VARCHAR(255) NOT NULL',
        'SELECT 1'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

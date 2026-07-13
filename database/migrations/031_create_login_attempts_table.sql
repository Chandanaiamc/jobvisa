-- =============================================================================
-- Migration : 031_create_login_attempts_table
-- Project   : JobVisa.lk Enterprise
-- Target    : MariaDB 10.4+
-- Purpose   : Brute-force / lockout analytics for authentication
-- =============================================================================

CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(191) NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` VARCHAR(500) NULL,
    `was_successful` TINYINT(1) NOT NULL DEFAULT 0,
    `attempted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_login_attempts_email` (`email`),
    KEY `idx_login_attempts_ip_address` (`ip_address`),
    KEY `idx_login_attempts_attempted_at` (`attempted_at`),
    KEY `idx_login_attempts_email_attempted` (`email`, `attempted_at`),
    KEY `idx_login_attempts_ip_attempted` (`ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

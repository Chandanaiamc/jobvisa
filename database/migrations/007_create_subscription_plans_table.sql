-- Migration: create subscription_plans table
-- JobVisa.lk

CREATE TABLE IF NOT EXISTS `subscription_plans` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(50) NOT NULL,
    `name` VARCHAR(120) NOT NULL,
    `audience` VARCHAR(32) NOT NULL COMMENT 'employer|seeker',
    `description` TEXT DEFAULT NULL,
    `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `currency` CHAR(3) NOT NULL DEFAULT 'LKR',
    `duration_days` INT UNSIGNED NOT NULL DEFAULT 30,
    `job_post_limit` INT UNSIGNED DEFAULT NULL,
    `features_json` JSON DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_subscription_plans_code` (`code`),
    KEY `idx_subscription_plans_audience_active` (`audience`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

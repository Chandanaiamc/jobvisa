-- Migration: create users table
-- JobVisa.lk

CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(191) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(32) DEFAULT NULL,
    `role` VARCHAR(32) NOT NULL DEFAULT 'seeker' COMMENT 'seeker|employer|admin|staff',
    `status` VARCHAR(32) NOT NULL DEFAULT 'active' COMMENT 'active|pending|suspended|deleted',
    `email_verified_at` DATETIME(3) DEFAULT NULL,
    `phone_verified_at` DATETIME(3) DEFAULT NULL,
    `last_login_at` DATETIME(3) DEFAULT NULL,
    `last_login_ip` VARCHAR(45) DEFAULT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`),
    KEY `idx_users_role_status` (`role`, `status`),
    KEY `idx_users_phone` (`phone`),
    KEY `idx_users_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

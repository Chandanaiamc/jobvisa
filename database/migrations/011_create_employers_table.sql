-- Migration: create employers table
-- JobVisa.lk

CREATE TABLE IF NOT EXISTS `employers` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `company_id` BIGINT UNSIGNED NOT NULL,
    `job_title` VARCHAR(150) DEFAULT NULL,
    `verified_status` VARCHAR(32) NOT NULL DEFAULT 'pending' COMMENT 'pending|verified|rejected',
    `verified_at` DATETIME(3) DEFAULT NULL,
    `verified_by` BIGINT UNSIGNED DEFAULT NULL,
    `billing_email` VARCHAR(191) DEFAULT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_employers_user_id` (`user_id`),
    KEY `idx_employers_company_id` (`company_id`),
    KEY `idx_employers_verified_status` (`verified_status`),
    KEY `idx_employers_verified_by` (`verified_by`),
    CONSTRAINT `fk_employers_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_employers_company`
        FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_employers_verified_by`
        FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

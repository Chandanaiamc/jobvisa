-- Migration: create payments table
-- JobVisa.lk

CREATE TABLE IF NOT EXISTS `payments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED DEFAULT NULL,
    `employer_id` BIGINT UNSIGNED DEFAULT NULL,
    `subscription_plan_id` BIGINT UNSIGNED DEFAULT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `currency` CHAR(3) NOT NULL DEFAULT 'LKR',
    `provider` VARCHAR(50) NOT NULL DEFAULT 'manual' COMMENT 'payhere|stripe|bank|manual',
    `provider_ref` VARCHAR(191) DEFAULT NULL,
    `status` VARCHAR(32) NOT NULL DEFAULT 'pending' COMMENT 'pending|paid|failed|refunded',
    `paid_at` DATETIME(3) DEFAULT NULL,
    `meta_json` JSON DEFAULT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    KEY `idx_payments_user_id` (`user_id`),
    KEY `idx_payments_employer_id` (`employer_id`),
    KEY `idx_payments_plan_id` (`subscription_plan_id`),
    KEY `idx_payments_status_created` (`status`, `created_at`),
    KEY `idx_payments_provider_ref` (`provider_ref`),
    KEY `idx_payments_created_at` (`created_at`),
    CONSTRAINT `fk_payments_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_payments_employer`
        FOREIGN KEY (`employer_id`) REFERENCES `employers` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_payments_subscription_plan`
        FOREIGN KEY (`subscription_plan_id`) REFERENCES `subscription_plans` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

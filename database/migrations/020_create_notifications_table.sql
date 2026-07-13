-- Migration: create notifications table
-- JobVisa.lk

CREATE TABLE IF NOT EXISTS `notifications` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `type` VARCHAR(80) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `body` TEXT DEFAULT NULL,
    `data_json` JSON DEFAULT NULL,
    `read_at` DATETIME(3) DEFAULT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    KEY `idx_notifications_user_unread` (`user_id`, `read_at`, `id`),
    KEY `idx_notifications_user_created` (`user_id`, `created_at`),
    KEY `idx_notifications_type` (`type`),
    CONSTRAINT `fk_notifications_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migration: create user_languages table
-- JobVisa.lk

CREATE TABLE IF NOT EXISTS `user_languages` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `language_id` BIGINT UNSIGNED NOT NULL,
    `proficiency` VARCHAR(32) NOT NULL DEFAULT 'conversational' COMMENT 'basic|conversational|fluent|native',
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_user_languages_user_language` (`user_id`, `language_id`),
    KEY `idx_user_languages_language_id` (`language_id`),
    CONSTRAINT `fk_user_languages_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_user_languages_language`
        FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

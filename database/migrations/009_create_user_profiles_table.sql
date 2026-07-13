-- Migration: create user_profiles table
-- JobVisa.lk

CREATE TABLE IF NOT EXISTS `user_profiles` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `headline` VARCHAR(255) DEFAULT NULL,
    `summary` TEXT DEFAULT NULL,
    `date_of_birth` DATE DEFAULT NULL,
    `gender` VARCHAR(20) DEFAULT NULL,
    `nationality_country_id` BIGINT UNSIGNED DEFAULT NULL,
    `current_city_id` BIGINT UNSIGNED DEFAULT NULL,
    `preferred_country_id` BIGINT UNSIGNED DEFAULT NULL,
    `linkedin_url` VARCHAR(512) DEFAULT NULL,
    `avatar_path` VARCHAR(512) DEFAULT NULL,
    `visibility` VARCHAR(32) NOT NULL DEFAULT 'employers' COMMENT 'public|employers|private',
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_user_profiles_user_id` (`user_id`),
    KEY `idx_user_profiles_nationality` (`nationality_country_id`),
    KEY `idx_user_profiles_current_city` (`current_city_id`),
    KEY `idx_user_profiles_preferred_country` (`preferred_country_id`),
    CONSTRAINT `fk_user_profiles_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_user_profiles_nationality_country`
        FOREIGN KEY (`nationality_country_id`) REFERENCES `countries` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_user_profiles_current_city`
        FOREIGN KEY (`current_city_id`) REFERENCES `cities` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_user_profiles_preferred_country`
        FOREIGN KEY (`preferred_country_id`) REFERENCES `countries` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

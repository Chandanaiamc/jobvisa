-- Migration: create cities table
-- JobVisa.lk

CREATE TABLE IF NOT EXISTS `cities` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `country_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(120) NOT NULL,
    `slug` VARCHAR(150) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_cities_country_slug` (`country_id`, `slug`),
    KEY `idx_cities_country_id` (`country_id`),
    KEY `idx_cities_name` (`name`),
    KEY `idx_cities_active` (`is_active`),
    CONSTRAINT `fk_cities_country`
        FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

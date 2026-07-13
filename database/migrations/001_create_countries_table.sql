-- Migration: create countries table
-- JobVisa.lk

CREATE TABLE IF NOT EXISTS `countries` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `iso2` CHAR(2) NOT NULL,
    `iso3` CHAR(3) NOT NULL,
    `phone_code` VARCHAR(8) DEFAULT NULL,
    `is_job_destination` TINYINT(1) NOT NULL DEFAULT 1,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_countries_iso2` (`iso2`),
    UNIQUE KEY `uq_countries_iso3` (`iso3`),
    KEY `idx_countries_name` (`name`),
    KEY `idx_countries_active_destination` (`is_active`, `is_job_destination`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

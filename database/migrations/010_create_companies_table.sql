-- Migration: create companies table
-- JobVisa.lk

CREATE TABLE IF NOT EXISTS `companies` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(191) NOT NULL,
    `registration_no` VARCHAR(100) DEFAULT NULL,
    `website` VARCHAR(512) DEFAULT NULL,
    `logo_path` VARCHAR(512) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `industry` VARCHAR(100) DEFAULT NULL,
    `company_size` VARCHAR(50) DEFAULT NULL,
    `hq_country_id` BIGINT UNSIGNED DEFAULT NULL,
    `hq_city_id` BIGINT UNSIGNED DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_companies_slug` (`slug`),
    KEY `idx_companies_name` (`name`),
    KEY `idx_companies_industry` (`industry`),
    KEY `idx_companies_hq_country` (`hq_country_id`),
    KEY `idx_companies_active` (`is_active`),
    CONSTRAINT `fk_companies_hq_country`
        FOREIGN KEY (`hq_country_id`) REFERENCES `countries` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_companies_hq_city`
        FOREIGN KEY (`hq_city_id`) REFERENCES `cities` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

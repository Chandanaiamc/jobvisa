-- =============================================================================
-- Migration : 034_create_resume_personal
-- Project   : JobVisa.lk Enterprise
-- Purpose   : Sprint 2D.2 — resume-specific personal overrides only
--             (profile fields remain on user_profiles / users)
-- Depends   : 013_create_resumes_table, 001_create_countries_table
-- =============================================================================

CREATE TABLE IF NOT EXISTS `resume_personal` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `passport_number` VARCHAR(64) NULL DEFAULT NULL,
    `passport_expiry` DATE NULL DEFAULT NULL,
    `salary_currency` CHAR(3) NULL DEFAULT NULL,
    `visa_status` VARCHAR(64) NULL DEFAULT NULL,
    `driving_licence_status` VARCHAR(64) NULL DEFAULT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_resume_personal_resume_id` (`resume_id`),
    CONSTRAINT `fk_resume_personal_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `resume_preferred_countries` (
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `country_id` BIGINT UNSIGNED NOT NULL,
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`resume_id`, `country_id`),
    KEY `idx_rpc_country_id` (`country_id`),
    CONSTRAINT `fk_rpc_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_rpc_country`
        FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- Migration : 046_create_resume_references
-- Project   : JobVisa.lk Enterprise
-- Purpose   : Sprint 2E.3 — professional references
-- Depends   : 013_create_resumes_table, 001_countries, 002_cities, 041_projects
-- Preserves : all existing tables (isolated create)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `resume_references` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `project_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `country_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `city_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `name` VARCHAR(200) NOT NULL,
    `designation` VARCHAR(200) NULL DEFAULT NULL,
    `company` VARCHAR(200) NULL DEFAULT NULL,
    `email` VARCHAR(255) NULL DEFAULT NULL,
    `phone` VARCHAR(40) NULL DEFAULT NULL,
    `relationship` VARCHAR(120) NULL DEFAULT NULL,
    `years_known` DECIMAL(4,1) NULL DEFAULT NULL,
    `permission_to_contact` TINYINT(1) NOT NULL DEFAULT 0,
    `notes` TEXT NULL DEFAULT NULL,
    `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
    `visibility` VARCHAR(32) NOT NULL DEFAULT 'private',
    `status` VARCHAR(32) NOT NULL DEFAULT 'active',
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    `deleted_at` DATETIME(3) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_resume_references_resume` (`resume_id`),
    KEY `idx_resume_references_visibility` (`resume_id`, `visibility`, `deleted_at`),
    KEY `idx_resume_references_status` (`resume_id`, `status`, `deleted_at`),
    KEY `idx_resume_references_featured` (`resume_id`, `is_featured`, `deleted_at`),
    KEY `idx_resume_references_sort` (`resume_id`, `sort_order`),
    KEY `idx_resume_references_deleted` (`resume_id`, `deleted_at`),
    KEY `idx_resume_references_project` (`project_id`),
    KEY `idx_resume_references_country` (`country_id`),
    KEY `idx_resume_references_city` (`city_id`),
    KEY `idx_resume_references_name` (`resume_id`, `name`(100)),
    CONSTRAINT `fk_resume_references_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_resume_references_project`
        FOREIGN KEY (`project_id`) REFERENCES `resume_projects` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_resume_references_country`
        FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_resume_references_city`
        FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

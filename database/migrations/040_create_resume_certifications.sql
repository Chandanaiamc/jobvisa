-- =============================================================================
-- Migration : 040_create_resume_certifications
-- Project   : JobVisa.lk Enterprise
-- Purpose   : Sprint 2D.8 — resume certifications & licences
-- Depends   : 013_create_resumes_table
-- Preserves : all existing tables (not tied to user profile)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `resume_certifications` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(200) NOT NULL,
    `issuing_organization` VARCHAR(200) NOT NULL,
    `credential_id` VARCHAR(120) NULL DEFAULT NULL,
    `credential_url` VARCHAR(500) NULL DEFAULT NULL,
    `issue_date` DATE NULL DEFAULT NULL,
    `expiry_date` DATE NULL DEFAULT NULL,
    `does_not_expire` TINYINT(1) NOT NULL DEFAULT 0,
    `license_number` VARCHAR(120) NULL DEFAULT NULL,
    `verification_url` VARCHAR(500) NULL DEFAULT NULL,
    `certificate_path` VARCHAR(500) NULL DEFAULT NULL,
    `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `status` VARCHAR(32) NOT NULL DEFAULT 'active',
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    `deleted_at` DATETIME(3) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_resume_certifications_resume` (`resume_id`),
    KEY `idx_resume_certifications_deleted` (`resume_id`, `deleted_at`),
    KEY `idx_resume_certifications_sort` (`resume_id`, `sort_order`),
    CONSTRAINT `fk_resume_certifications_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

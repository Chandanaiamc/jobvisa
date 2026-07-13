-- =============================================================================
-- Migration : 035_create_resume_professional
-- Project   : JobVisa.lk Enterprise
-- Purpose   : Sprint 2D.3 — headline & professional summary section
-- Depends   : 013_create_resumes_table
-- =============================================================================

CREATE TABLE IF NOT EXISTS `resume_professional` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `headline` VARCHAR(255) NULL DEFAULT NULL,
    `summary` TEXT NULL DEFAULT NULL,
    `career_objective` TEXT NULL DEFAULT NULL,
    `years_of_experience` DECIMAL(4,1) NULL DEFAULT NULL,
    `current_job_title` VARCHAR(150) NULL DEFAULT NULL,
    `current_company` VARCHAR(200) NULL DEFAULT NULL,
    `industry` VARCHAR(150) NULL DEFAULT NULL,
    `current_salary` DECIMAL(12,2) NULL DEFAULT NULL,
    `expected_salary` DECIMAL(12,2) NULL DEFAULT NULL,
    `preferred_currency` CHAR(3) NULL DEFAULT NULL,
    `notice_period` VARCHAR(64) NULL DEFAULT NULL,
    `employment_status` VARCHAR(64) NULL DEFAULT NULL,
    `open_to_relocate` TINYINT(1) NOT NULL DEFAULT 0,
    `open_to_remote` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_resume_professional_resume_id` (`resume_id`),
    CONSTRAINT `fk_resume_professional_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

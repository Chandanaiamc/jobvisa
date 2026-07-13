-- =============================================================================
-- Migration : 042_create_resume_achievements
-- Project   : JobVisa.lk Enterprise
-- Purpose   : Sprint 2E.1 — resume awards & achievements
-- Depends   : 013_create_resumes_table, 041_create_resume_projects
-- Preserves : all existing tables (resume-scoped)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `resume_achievements` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `project_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `title` VARCHAR(200) NOT NULL,
    `issuer` VARCHAR(200) NULL DEFAULT NULL,
    `description` TEXT NULL DEFAULT NULL,
    `achievement_type` VARCHAR(64) NULL DEFAULT NULL,
    `achievement_date` DATE NULL DEFAULT NULL,
    `credential_url` VARCHAR(500) NULL DEFAULT NULL,
    `certificate_path` VARCHAR(500) NULL DEFAULT NULL,
    `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
    `visibility` VARCHAR(32) NOT NULL DEFAULT 'public',
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `status` VARCHAR(32) NOT NULL DEFAULT 'active',
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    `deleted_at` DATETIME(3) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_resume_achievements_resume` (`resume_id`),
    KEY `idx_resume_achievements_deleted` (`resume_id`, `deleted_at`),
    KEY `idx_resume_achievements_sort` (`resume_id`, `sort_order`),
    KEY `idx_resume_achievements_featured` (`resume_id`, `is_featured`, `deleted_at`),
    KEY `idx_resume_achievements_visibility` (`resume_id`, `visibility`, `deleted_at`),
    KEY `idx_resume_achievements_project` (`project_id`),
    CONSTRAINT `fk_resume_achievements_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_resume_achievements_project`
        FOREIGN KEY (`project_id`) REFERENCES `resume_projects` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

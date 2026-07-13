-- =============================================================================
-- Migration : 041_create_resume_projects
-- Project   : JobVisa.lk Enterprise
-- Purpose   : Sprint 2D.9 — resume projects & portfolio
-- Depends   : 013_create_resumes_table
-- Preserves : all existing tables (resume-scoped; not profile)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `resume_projects` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `client_name` VARCHAR(200) NULL DEFAULT NULL,
    `organization` VARCHAR(200) NULL DEFAULT NULL,
    `role` VARCHAR(150) NULL DEFAULT NULL,
    `description` TEXT NULL DEFAULT NULL,
    `technologies` TEXT NULL DEFAULT NULL,
    `project_url` VARCHAR(500) NULL DEFAULT NULL,
    `github_url` VARCHAR(500) NULL DEFAULT NULL,
    `portfolio_url` VARCHAR(500) NULL DEFAULT NULL,
    `video_demo_url` VARCHAR(500) NULL DEFAULT NULL,
    `image` VARCHAR(500) NULL DEFAULT NULL,
    `document` VARCHAR(500) NULL DEFAULT NULL,
    `start_date` DATE NULL DEFAULT NULL,
    `end_date` DATE NULL DEFAULT NULL,
    `currently_working` TINYINT(1) NOT NULL DEFAULT 0,
    `team_size` SMALLINT UNSIGNED NULL DEFAULT NULL,
    `project_type` VARCHAR(64) NULL DEFAULT NULL,
    `industry` VARCHAR(150) NULL DEFAULT NULL,
    `location` VARCHAR(200) NULL DEFAULT NULL,
    `achievements` TEXT NULL DEFAULT NULL,
    `responsibilities` TEXT NULL DEFAULT NULL,
    `status` VARCHAR(32) NOT NULL DEFAULT 'active',
    `visibility` VARCHAR(32) NOT NULL DEFAULT 'public',
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    `deleted_at` DATETIME(3) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_resume_projects_resume` (`resume_id`),
    KEY `idx_resume_projects_deleted` (`resume_id`, `deleted_at`),
    KEY `idx_resume_projects_sort` (`resume_id`, `sort_order`),
    KEY `idx_resume_projects_visibility` (`resume_id`, `visibility`, `deleted_at`),
    CONSTRAINT `fk_resume_projects_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

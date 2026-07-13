-- =============================================================================
-- Migration : 038_create_resume_skills
-- Project   : JobVisa.lk Enterprise
-- Purpose   : Sprint 2D.6 — resume-scoped skills (catalogue = `skills`, not user_skills)
-- Depends   : 005_create_skills_table, 013_create_resumes_table
-- Preserves : skills catalogue + user_skills untouched
-- =============================================================================

CREATE TABLE IF NOT EXISTS `resume_skills` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `skill_id` BIGINT UNSIGNED NOT NULL,
    `level` VARCHAR(32) NOT NULL DEFAULT 'intermediate'
        COMMENT 'beginner|intermediate|advanced|expert',
    `years_experience` DECIMAL(4,1) NULL DEFAULT NULL,
    `last_used_year` SMALLINT UNSIGNED NULL DEFAULT NULL,
    `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `status` VARCHAR(32) NOT NULL DEFAULT 'active',
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    `deleted_at` DATETIME(3) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_resume_skills_resume_skill` (`resume_id`, `skill_id`),
    KEY `idx_resume_skills_skill_id` (`skill_id`),
    KEY `idx_resume_skills_deleted` (`resume_id`, `deleted_at`),
    KEY `idx_resume_skills_sort` (`resume_id`, `sort_order`),
    CONSTRAINT `fk_resume_skills_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_resume_skills_skill`
        FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

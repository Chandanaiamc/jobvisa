-- =============================================================================
-- Migration : 058_create_skill_gap_tables
-- Project   : JobVisa.lk Enterprise
-- Purpose   : Sprint 3.4 — AI Skill Gap Analyzer analyses + history
-- Depends   : resumes, users, jobs
-- Preserves : no destructive changes to prior tables
-- =============================================================================

CREATE TABLE IF NOT EXISTS `skill_gap_analyses` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `job_id` BIGINT UNSIGNED NOT NULL,
    `gap_percentage` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `readiness_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `match_skills_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `matched_count` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `missing_count` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `job_title` VARCHAR(191) NOT NULL DEFAULT '',
    `analysis_json` JSON NOT NULL,
    `rules_version` VARCHAR(32) NOT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    `deleted_at` DATETIME(3) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_sga_resume_created` (`resume_id`, `created_at`, `deleted_at`),
    KEY `idx_sga_resume_job` (`resume_id`, `job_id`, `deleted_at`),
    KEY `idx_sga_user` (`user_id`, `deleted_at`),
    CONSTRAINT `fk_sga_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sga_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sga_job`
        FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `skill_gap_history` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `job_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `analysis_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `action` VARCHAR(32) NOT NULL DEFAULT 'analyze',
    `headline` VARCHAR(255) NOT NULL DEFAULT '',
    `gap_percentage` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `readiness_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `snapshot_json` JSON NOT NULL,
    `rules_version` VARCHAR(32) NOT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    `deleted_at` DATETIME(3) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_sgh_resume_created` (`resume_id`, `created_at`, `deleted_at`),
    KEY `idx_sgh_resume_deleted` (`resume_id`, `deleted_at`),
    KEY `idx_sgh_analysis` (`analysis_id`),
    CONSTRAINT `fk_sgh_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sgh_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sgh_job`
        FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_sgh_analysis`
        FOREIGN KEY (`analysis_id`) REFERENCES `skill_gap_analyses` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

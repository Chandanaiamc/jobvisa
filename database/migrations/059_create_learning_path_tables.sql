-- =============================================================================
-- Migration : 059_create_learning_path_tables
-- Project   : JobVisa.lk Enterprise
-- Purpose   : Sprint 3.5 — AI Learning Path Generator paths + history
-- Depends   : resumes, users, jobs
-- Preserves : no destructive changes to prior tables
-- =============================================================================

CREATE TABLE IF NOT EXISTS `learning_paths` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `job_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `career_goal` VARCHAR(255) NOT NULL DEFAULT '',
    `timeline_weeks` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `progress_percent` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `milestones_total` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `milestones_done` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `alignment_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `path_json` JSON NOT NULL,
    `rules_version` VARCHAR(32) NOT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    `deleted_at` DATETIME(3) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_lp_resume_created` (`resume_id`, `created_at`, `deleted_at`),
    KEY `idx_lp_user` (`user_id`, `deleted_at`),
    KEY `idx_lp_progress` (`resume_id`, `progress_percent`),
    CONSTRAINT `fk_lp_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_lp_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_lp_job`
        FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `learning_path_history` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `path_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `action` VARCHAR(32) NOT NULL DEFAULT 'generate',
    `headline` VARCHAR(255) NOT NULL DEFAULT '',
    `progress_percent` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `timeline_weeks` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `snapshot_json` JSON NOT NULL,
    `rules_version` VARCHAR(32) NOT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    `deleted_at` DATETIME(3) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_lph_resume_created` (`resume_id`, `created_at`, `deleted_at`),
    KEY `idx_lph_resume_deleted` (`resume_id`, `deleted_at`),
    KEY `idx_lph_path` (`path_id`),
    CONSTRAINT `fk_lph_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_lph_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_lph_path`
        FOREIGN KEY (`path_id`) REFERENCES `learning_paths` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

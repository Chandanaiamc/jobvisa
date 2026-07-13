-- =============================================================================
-- Migration : 060_create_portfolio_builder_tables
-- Project   : JobVisa.lk Enterprise
-- Purpose   : Sprint 3.6 — AI Portfolio & Project Builder plans + history
-- Depends   : resumes, users, jobs
-- Preserves : no destructive changes to prior tables
-- =============================================================================

CREATE TABLE IF NOT EXISTS `portfolio_builder_plans` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `job_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `career_goal` VARCHAR(255) NOT NULL DEFAULT '',
    `strength_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `project_count` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `recruiter_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `plan_json` JSON NOT NULL,
    `rules_version` VARCHAR(32) NOT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    `deleted_at` DATETIME(3) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_pbp_resume_created` (`resume_id`, `created_at`, `deleted_at`),
    KEY `idx_pbp_user` (`user_id`, `deleted_at`),
    KEY `idx_pbp_strength` (`resume_id`, `strength_score`),
    CONSTRAINT `fk_pbp_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_pbp_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_pbp_job`
        FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `portfolio_builder_history` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `plan_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `action` VARCHAR(32) NOT NULL DEFAULT 'generate',
    `headline` VARCHAR(255) NOT NULL DEFAULT '',
    `strength_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `project_count` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `snapshot_json` JSON NOT NULL,
    `rules_version` VARCHAR(32) NOT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    `deleted_at` DATETIME(3) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_pbh_resume_created` (`resume_id`, `created_at`, `deleted_at`),
    KEY `idx_pbh_resume_deleted` (`resume_id`, `deleted_at`),
    KEY `idx_pbh_plan` (`plan_id`),
    CONSTRAINT `fk_pbh_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_pbh_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_pbh_plan`
        FOREIGN KEY (`plan_id`) REFERENCES `portfolio_builder_plans` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

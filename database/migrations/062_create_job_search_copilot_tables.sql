-- =============================================================================
-- Migration : 062_create_job_search_copilot_tables
-- Project   : JobVisa.lk Enterprise
-- Purpose   : Sprint 3.8 — AI Job Search Copilot plans + history
-- Depends   : resumes, users, jobs
-- Preserves : no destructive changes to prior tables
-- =============================================================================

CREATE TABLE IF NOT EXISTS `job_search_copilot_plans` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `career_goal` VARCHAR(255) NOT NULL DEFAULT '',
    `copilot_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `recommendation_count` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `plan_json` JSON NOT NULL,
    `rules_version` VARCHAR(32) NOT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    `deleted_at` DATETIME(3) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_jscp_resume_created` (`resume_id`, `created_at`, `deleted_at`),
    KEY `idx_jscp_user` (`user_id`, `deleted_at`),
    KEY `idx_jscp_score` (`resume_id`, `copilot_score`),
    CONSTRAINT `fk_jscp_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_jscp_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `job_search_copilot_history` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `plan_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `action` VARCHAR(32) NOT NULL DEFAULT 'generate',
    `headline` VARCHAR(255) NOT NULL DEFAULT '',
    `copilot_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `recommendation_count` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `snapshot_json` JSON NOT NULL,
    `rules_version` VARCHAR(32) NOT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    `deleted_at` DATETIME(3) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_jsch_resume_created` (`resume_id`, `created_at`, `deleted_at`),
    KEY `idx_jsch_resume_deleted` (`resume_id`, `deleted_at`),
    KEY `idx_jsch_plan` (`plan_id`),
    CONSTRAINT `fk_jsch_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_jsch_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_jsch_plan`
        FOREIGN KEY (`plan_id`) REFERENCES `job_search_copilot_plans` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

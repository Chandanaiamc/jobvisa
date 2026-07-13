-- =============================================================================
-- Migration : 053_create_career_coach_tables
-- Project   : JobVisa.lk Enterprise
-- Purpose   : Sprint 2F.8 — AI Career Coach sessions + recommendation history
-- Depends   : resumes, users
-- Preserves : no destructive changes to prior tables
-- =============================================================================

CREATE TABLE IF NOT EXISTS `career_coach_sessions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `target_role` VARCHAR(191) NULL DEFAULT NULL,
    `headline` VARCHAR(255) NOT NULL DEFAULT '',
    `summary_json` JSON NOT NULL,
    `skill_gaps_json` JSON NOT NULL,
    `next_roles_json` JSON NOT NULL,
    `learning_roadmap_json` JSON NOT NULL,
    `certification_recs_json` JSON NOT NULL,
    `portfolio_recs_json` JSON NOT NULL,
    `job_opportunities_json` JSON NOT NULL,
    `context_scores_json` JSON NOT NULL,
    `coach_version` VARCHAR(32) NOT NULL,
    `calculated_at` DATETIME(3) NOT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_career_coach_resume` (`resume_id`),
    KEY `idx_ccs_user` (`user_id`),
    KEY `idx_ccs_calculated` (`resume_id`, `calculated_at`),
    KEY `idx_ccs_version` (`coach_version`),
    CONSTRAINT `fk_ccs_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_ccs_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `career_coach_history` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `target_role` VARCHAR(191) NULL DEFAULT NULL,
    `headline` VARCHAR(255) NOT NULL DEFAULT '',
    `snapshot_json` JSON NOT NULL,
    `coach_version` VARCHAR(32) NOT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    `deleted_at` DATETIME(3) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_cch_resume_created` (`resume_id`, `created_at`, `deleted_at`),
    KEY `idx_cch_resume_deleted` (`resume_id`, `deleted_at`),
    KEY `idx_cch_user` (`user_id`, `deleted_at`),
    CONSTRAINT `fk_cch_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_cch_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

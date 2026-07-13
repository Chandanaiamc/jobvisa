-- =============================================================================
-- Migration : 061_create_mock_interview_tables
-- Project   : JobVisa.lk Enterprise
-- Purpose   : Sprint 3.7 — AI Mock Interview Simulator sessions + history
-- Depends   : resumes, users, jobs
-- Preserves : no destructive changes to prior tables
-- =============================================================================

CREATE TABLE IF NOT EXISTS `mock_interview_sessions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `job_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `job_title` VARCHAR(191) NOT NULL DEFAULT '',
    `career_level` VARCHAR(64) NOT NULL DEFAULT '',
    `status` VARCHAR(32) NOT NULL DEFAULT 'generated',
    `overall_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `communication_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `technical_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `confidence_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `star_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `session_json` JSON NOT NULL,
    `rules_version` VARCHAR(32) NOT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    `deleted_at` DATETIME(3) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_mis_resume_created` (`resume_id`, `created_at`, `deleted_at`),
    KEY `idx_mis_user` (`user_id`, `deleted_at`),
    KEY `idx_mis_overall` (`resume_id`, `overall_score`),
    CONSTRAINT `fk_mis_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_mis_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_mis_job`
        FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mock_interview_history` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `session_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `action` VARCHAR(32) NOT NULL DEFAULT 'generate',
    `headline` VARCHAR(255) NOT NULL DEFAULT '',
    `overall_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `snapshot_json` JSON NOT NULL,
    `rules_version` VARCHAR(32) NOT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    `deleted_at` DATETIME(3) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_mih_resume_created` (`resume_id`, `created_at`, `deleted_at`),
    KEY `idx_mih_resume_deleted` (`resume_id`, `deleted_at`),
    KEY `idx_mih_session` (`session_id`),
    CONSTRAINT `fk_mih_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_mih_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_mih_session`
        FOREIGN KEY (`session_id`) REFERENCES `mock_interview_sessions` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

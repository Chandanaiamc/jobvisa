-- =============================================================================
-- Migration : 056_create_application_assistant_tables
-- Project   : JobVisa.lk Enterprise
-- Purpose   : Sprint 3.2 — AI Application Assistant analyses + history
-- Depends   : resumes, users, jobs
-- Preserves : no destructive changes to prior tables
-- =============================================================================

CREATE TABLE IF NOT EXISTS `application_assistant_analyses` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `job_id` BIGINT UNSIGNED NOT NULL,
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `readiness_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `skills_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `experience_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `education_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `certification_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `portfolio_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `match_overall` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `resume_overall` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `analysis_json` JSON NOT NULL,
    `rules_version` VARCHAR(32) NOT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    `deleted_at` DATETIME(3) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_aaa_user_job` (`user_id`, `job_id`, `deleted_at`),
    KEY `idx_aaa_job_resume` (`job_id`, `resume_id`, `deleted_at`),
    KEY `idx_aaa_readiness` (`job_id`, `readiness_score`),
    CONSTRAINT `fk_aaa_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_aaa_job`
        FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_aaa_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `application_assistant_history` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `job_id` BIGINT UNSIGNED NOT NULL,
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `analysis_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `action` VARCHAR(32) NOT NULL DEFAULT 'analyze',
    `headline` VARCHAR(255) NOT NULL DEFAULT '',
    `readiness_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `snapshot_json` JSON NOT NULL,
    `rules_version` VARCHAR(32) NOT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    `deleted_at` DATETIME(3) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_aah_user_job_created` (`user_id`, `job_id`, `created_at`, `deleted_at`),
    KEY `idx_aah_job_deleted` (`job_id`, `deleted_at`),
    KEY `idx_aah_analysis` (`analysis_id`),
    CONSTRAINT `fk_aah_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_aah_job`
        FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_aah_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_aah_analysis`
        FOREIGN KEY (`analysis_id`) REFERENCES `application_assistant_analyses` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

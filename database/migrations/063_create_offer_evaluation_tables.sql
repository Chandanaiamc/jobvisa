-- =============================================================================
-- Migration : 063_create_offer_evaluation_tables
-- Project   : JobVisa.lk Enterprise
-- Purpose   : Sprint 3.9 — AI Offer Evaluation Assistant analyses + history
-- Depends   : resumes, users, jobs
-- Preserves : no destructive changes to prior tables
-- =============================================================================

CREATE TABLE IF NOT EXISTS `offer_evaluation_analyses` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `job_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `company_name` VARCHAR(191) NOT NULL DEFAULT '',
    `job_title` VARCHAR(191) NOT NULL DEFAULT '',
    `currency` CHAR(3) NOT NULL DEFAULT 'USD',
    `base_salary` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    `overall_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `compensation_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `benefits_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `growth_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `lifestyle_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `recommendation` VARCHAR(32) NOT NULL DEFAULT 'negotiate',
    `analysis_json` JSON NOT NULL,
    `rules_version` VARCHAR(32) NOT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    `deleted_at` DATETIME(3) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_oea_resume_created` (`resume_id`, `created_at`, `deleted_at`),
    KEY `idx_oea_user` (`user_id`, `deleted_at`),
    KEY `idx_oea_overall` (`resume_id`, `overall_score`),
    CONSTRAINT `fk_oea_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_oea_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_oea_job`
        FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `offer_evaluation_history` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `analysis_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `action` VARCHAR(32) NOT NULL DEFAULT 'evaluate',
    `headline` VARCHAR(255) NOT NULL DEFAULT '',
    `overall_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `recommendation` VARCHAR(32) NOT NULL DEFAULT '',
    `snapshot_json` JSON NOT NULL,
    `rules_version` VARCHAR(32) NOT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    `deleted_at` DATETIME(3) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_oeh_resume_created` (`resume_id`, `created_at`, `deleted_at`),
    KEY `idx_oeh_resume_deleted` (`resume_id`, `deleted_at`),
    KEY `idx_oeh_analysis` (`analysis_id`),
    CONSTRAINT `fk_oeh_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_oeh_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_oeh_analysis`
        FOREIGN KEY (`analysis_id`) REFERENCES `offer_evaluation_analyses` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

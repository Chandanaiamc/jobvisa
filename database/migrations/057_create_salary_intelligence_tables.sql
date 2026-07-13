-- =============================================================================
-- Migration : 057_create_salary_intelligence_tables
-- Project   : JobVisa.lk Enterprise
-- Purpose   : Sprint 3.3 — AI Salary Intelligence predictions + history
-- Depends   : resumes, users
-- Preserves : no destructive changes to prior tables
-- =============================================================================

CREATE TABLE IF NOT EXISTS `salary_intelligence_predictions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `currency` CHAR(3) NOT NULL DEFAULT 'USD',
    `predicted_salary` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `min_salary` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `max_salary` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `market_average` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `recommended_target` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `confidence_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `career_level` VARCHAR(64) NOT NULL DEFAULT '',
    `job_title` VARCHAR(191) NOT NULL DEFAULT '',
    `location_label` VARCHAR(191) NOT NULL DEFAULT '',
    `industry` VARCHAR(150) NOT NULL DEFAULT '',
    `analysis_json` JSON NOT NULL,
    `rules_version` VARCHAR(32) NOT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    `deleted_at` DATETIME(3) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_sip_resume_created` (`resume_id`, `created_at`, `deleted_at`),
    KEY `idx_sip_user` (`user_id`, `deleted_at`),
    KEY `idx_sip_confidence` (`resume_id`, `confidence_score`),
    CONSTRAINT `fk_sip_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sip_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `salary_intelligence_history` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `prediction_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `action` VARCHAR(32) NOT NULL DEFAULT 'calculate',
    `headline` VARCHAR(255) NOT NULL DEFAULT '',
    `predicted_salary` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `currency` CHAR(3) NOT NULL DEFAULT 'USD',
    `confidence_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `snapshot_json` JSON NOT NULL,
    `rules_version` VARCHAR(32) NOT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    `deleted_at` DATETIME(3) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_sih_resume_created` (`resume_id`, `created_at`, `deleted_at`),
    KEY `idx_sih_resume_deleted` (`resume_id`, `deleted_at`),
    KEY `idx_sih_prediction` (`prediction_id`),
    CONSTRAINT `fk_sih_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sih_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sih_prediction`
        FOREIGN KEY (`prediction_id`) REFERENCES `salary_intelligence_predictions` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

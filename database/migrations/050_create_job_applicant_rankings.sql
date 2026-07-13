-- =============================================================================
-- Migration : 050_create_job_applicant_rankings
-- Project   : JobVisa.lk Enterprise
-- Purpose   : Sprint 2F.4 — applicant ranking snapshots + history
-- Depends   : jobs, applications
-- Preserves : no destructive changes to existing tables
-- =============================================================================

CREATE TABLE IF NOT EXISTS `job_applicant_rankings` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `job_id` BIGINT UNSIGNED NOT NULL,
    `application_id` BIGINT UNSIGNED NOT NULL,
    `resume_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `applicant_user_id` BIGINT UNSIGNED NOT NULL,
    `rank_position` INT UNSIGNED NOT NULL DEFAULT 0,
    `overall_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `resume_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `job_match_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `skills_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `experience_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `education_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `certification_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `portfolio_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `references_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `score_breakdown` JSON NOT NULL,
    `explanation_json` JSON NOT NULL,
    `rules_version` VARCHAR(32) NOT NULL,
    `calculated_at` DATETIME(3) NOT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    `deleted_at` DATETIME(3) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_job_application_ranking` (`job_id`, `application_id`),
    KEY `idx_jar_job_rank` (`job_id`, `rank_position`, `deleted_at`),
    KEY `idx_jar_job_score` (`job_id`, `overall_score`, `deleted_at`),
    KEY `idx_jar_applicant` (`applicant_user_id`),
    KEY `idx_jar_resume` (`resume_id`),
    KEY `idx_jar_rules` (`rules_version`),
    CONSTRAINT `fk_jar_job`
        FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_jar_application`
        FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_jar_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_jar_applicant`
        FOREIGN KEY (`applicant_user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `job_applicant_ranking_history` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `job_id` BIGINT UNSIGNED NOT NULL,
    `application_id` BIGINT UNSIGNED NOT NULL,
    `resume_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `applicant_user_id` BIGINT UNSIGNED NOT NULL,
    `rank_position` INT UNSIGNED NOT NULL DEFAULT 0,
    `overall_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `resume_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `job_match_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `skills_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `experience_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `education_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `certification_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `portfolio_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `references_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `score_breakdown` JSON NOT NULL,
    `explanation_json` JSON NOT NULL,
    `application_status` VARCHAR(32) NULL DEFAULT NULL,
    `rules_version` VARCHAR(32) NOT NULL,
    `calculated_at` DATETIME(3) NOT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    `deleted_at` DATETIME(3) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_jarh_job_calc` (`job_id`, `calculated_at`, `deleted_at`),
    KEY `idx_jarh_job_app` (`job_id`, `application_id`, `deleted_at`),
    KEY `idx_jarh_overall` (`job_id`, `overall_score`),
    CONSTRAINT `fk_jarh_job`
        FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_jarh_application`
        FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

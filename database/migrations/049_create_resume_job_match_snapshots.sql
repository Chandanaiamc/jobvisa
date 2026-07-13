-- =============================================================================
-- Migration : 049_create_resume_job_match_snapshots
-- Project   : JobVisa.lk Enterprise
-- Purpose   : Sprint 2F.3 — cache deterministic resume↔job match results
-- Depends   : resumes, jobs
-- Preserves : no alterations to jobs/resumes beyond new table
-- =============================================================================

CREATE TABLE IF NOT EXISTS `resume_job_match_snapshots` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `job_id` BIGINT UNSIGNED NOT NULL,
    `overall_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `skills_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `experience_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `education_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `language_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `certification_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `location_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `score_breakdown` JSON NOT NULL,
    `explanation_json` JSON NOT NULL,
    `recommendations` JSON NOT NULL,
    `rules_version` VARCHAR(32) NOT NULL,
    `calculated_at` DATETIME(3) NOT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    `deleted_at` DATETIME(3) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_resume_job_match` (`resume_id`, `job_id`),
    KEY `idx_rjm_resume_score` (`resume_id`, `overall_score`, `deleted_at`),
    KEY `idx_rjm_job` (`job_id`, `deleted_at`),
    KEY `idx_rjm_calculated` (`resume_id`, `calculated_at`),
    KEY `idx_rjm_rules` (`rules_version`),
    CONSTRAINT `fk_rjm_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_rjm_job`
        FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

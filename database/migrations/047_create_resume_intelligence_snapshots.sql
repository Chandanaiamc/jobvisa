-- =============================================================================
-- Migration : 047_create_resume_intelligence_snapshots
-- Project   : JobVisa.lk Enterprise
-- Purpose   : Sprint 2F.1 — resume intelligence score snapshots (additive)
-- Depends   : 013_create_resumes_table (or equivalent resumes table)
-- Preserves : all existing tables and completion scoring (isolated create)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `resume_intelligence_snapshots` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `overall_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `ats_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `employer_readiness_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `strength_level` VARCHAR(32) NOT NULL DEFAULT 'needs_improvement',
    `score_breakdown` JSON NOT NULL,
    `recommendations` JSON NOT NULL,
    `rules_version` VARCHAR(32) NOT NULL,
    `calculated_at` DATETIME(3) NOT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_resume_intelligence_resume` (`resume_id`),
    KEY `idx_resume_intelligence_overall` (`overall_score`),
    KEY `idx_resume_intelligence_ats` (`ats_score`),
    KEY `idx_resume_intelligence_employer` (`employer_readiness_score`),
    KEY `idx_resume_intelligence_calculated` (`calculated_at`),
    KEY `idx_resume_intelligence_version` (`rules_version`),
    CONSTRAINT `fk_resume_intelligence_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- Migration : 048_extend_resume_intelligence_engine
-- Project   : JobVisa.lk Enterprise
-- Purpose   : Sprint 2F.2 — keyword/skill-gap analysis columns + score history
-- Depends   : 047_create_resume_intelligence_snapshots
-- Preserves : existing snapshot rows; additive only
-- =============================================================================

ALTER TABLE `resume_intelligence_snapshots`
    ADD COLUMN IF NOT EXISTS `keyword_match_score` TINYINT UNSIGNED NOT NULL DEFAULT 0
        AFTER `employer_readiness_score`,
    ADD COLUMN IF NOT EXISTS `analysis_json` JSON NULL
        AFTER `recommendations`;

CREATE TABLE IF NOT EXISTS `resume_intelligence_history` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `overall_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `ats_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `employer_readiness_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `keyword_match_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `strength_level` VARCHAR(32) NOT NULL DEFAULT 'needs_improvement',
    `score_breakdown` JSON NOT NULL,
    `recommendations` JSON NOT NULL,
    `analysis_json` JSON NULL,
    `target_role` VARCHAR(200) NULL DEFAULT NULL,
    `rules_version` VARCHAR(32) NOT NULL,
    `calculated_at` DATETIME(3) NOT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    `deleted_at` DATETIME(3) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_intel_history_resume` (`resume_id`, `deleted_at`),
    KEY `idx_intel_history_calculated` (`resume_id`, `calculated_at`),
    KEY `idx_intel_history_overall` (`resume_id`, `overall_score`),
    CONSTRAINT `fk_intel_history_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

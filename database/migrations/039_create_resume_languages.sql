-- =============================================================================
-- Migration : 039_create_resume_languages
-- Project   : JobVisa.lk Enterprise
-- Purpose   : Sprint 2D.7 — resume-scoped languages (catalogue = `languages`)
-- Depends   : 006_create_languages_table, 013_create_resumes_table
-- Preserves : languages catalogue + user_languages untouched
-- =============================================================================

CREATE TABLE IF NOT EXISTS `resume_languages` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `language_id` BIGINT UNSIGNED NOT NULL,
    `speaking` VARCHAR(8) NOT NULL DEFAULT 'B1' COMMENT 'CEFR A1-C2',
    `reading` VARCHAR(8) NOT NULL DEFAULT 'B1',
    `writing` VARCHAR(8) NOT NULL DEFAULT 'B1',
    `listening` VARCHAR(8) NOT NULL DEFAULT 'B1',
    `is_native` TINYINT(1) NOT NULL DEFAULT 0,
    `certificate_type` VARCHAR(64) NULL DEFAULT NULL,
    `certificate_score` VARCHAR(32) NULL DEFAULT NULL,
    `certificate_issued_at` DATE NULL DEFAULT NULL,
    `certificate_expires_at` DATE NULL DEFAULT NULL,
    `certificate_path` VARCHAR(500) NULL DEFAULT NULL,
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `status` VARCHAR(32) NOT NULL DEFAULT 'active',
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    `deleted_at` DATETIME(3) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_resume_languages_resume_language` (`resume_id`, `language_id`),
    KEY `idx_resume_languages_language_id` (`language_id`),
    KEY `idx_resume_languages_deleted` (`resume_id`, `deleted_at`),
    KEY `idx_resume_languages_sort` (`resume_id`, `sort_order`),
    CONSTRAINT `fk_resume_languages_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_resume_languages_language`
        FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

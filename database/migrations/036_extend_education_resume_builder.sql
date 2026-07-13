-- =============================================================================
-- Migration : 036_extend_education_resume_builder
-- Project   : JobVisa.lk Enterprise
-- Purpose   : Sprint 2D.4 — additive columns for Resume Education (reuse table)
-- Depends   : 014_create_education_table, 032_extend_job_seeker_profile, 001 countries
-- Preserves : all existing education rows
-- =============================================================================

ALTER TABLE `education`
    ADD COLUMN IF NOT EXISTS `qualification_type` VARCHAR(64) NULL AFTER `institution`,
    ADD COLUMN IF NOT EXISTS `country_id` BIGINT UNSIGNED NULL AFTER `grade`,
    ADD COLUMN IF NOT EXISTS `city` VARCHAR(120) NULL AFTER `country_id`,
    ADD COLUMN IF NOT EXISTS `status` VARCHAR(32) NOT NULL DEFAULT 'active' AFTER `sort_order`,
    ADD COLUMN IF NOT EXISTS `deleted_at` DATETIME(3) NULL DEFAULT NULL AFTER `updated_at`;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'education'
              AND INDEX_NAME = 'idx_education_country_id'
        ),
        'SELECT 1',
        'ALTER TABLE `education` ADD INDEX `idx_education_country_id` (`country_id`)'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'education'
              AND INDEX_NAME = 'idx_education_deleted_at'
        ),
        'SELECT 1',
        'ALTER TABLE `education` ADD INDEX `idx_education_deleted_at` (`resume_id`, `deleted_at`)'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'education'
              AND COLUMN_NAME = 'country_id'
              AND REFERENCED_TABLE_NAME = 'countries'
        ),
        'SELECT 1',
        'ALTER TABLE `education` ADD CONSTRAINT `fk_education_country`
            FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`)
            ON DELETE SET NULL ON UPDATE CASCADE'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

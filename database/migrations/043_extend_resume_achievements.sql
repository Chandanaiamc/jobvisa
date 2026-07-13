-- =============================================================================
-- Migration : 043_extend_resume_achievements
-- Project   : JobVisa.lk Enterprise
-- Purpose   : Sprint 2E.1.1 — additive completeness fields for achievements
-- Depends   : 042_create_resume_achievements, 001_countries, 002_cities
-- Preserves : all existing resume_achievements rows (additive only)
-- =============================================================================

ALTER TABLE `resume_achievements`
    ADD COLUMN IF NOT EXISTS `country_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `project_id`,
    ADD COLUMN IF NOT EXISTS `city_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `country_id`,
    ADD COLUMN IF NOT EXISTS `award_level` VARCHAR(32) NULL DEFAULT NULL AFTER `achievement_type`,
    ADD COLUMN IF NOT EXISTS `rank_or_placement` VARCHAR(120) NULL DEFAULT NULL AFTER `award_level`,
    ADD COLUMN IF NOT EXISTS `remarks` TEXT NULL DEFAULT NULL AFTER `description`,
    ADD COLUMN IF NOT EXISTS `certificate_original_name` VARCHAR(255) NULL DEFAULT NULL AFTER `certificate_path`,
    ADD COLUMN IF NOT EXISTS `certificate_mime` VARCHAR(100) NULL DEFAULT NULL AFTER `certificate_original_name`,
    ADD COLUMN IF NOT EXISTS `certificate_size` INT UNSIGNED NULL DEFAULT NULL AFTER `certificate_mime`;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'resume_achievements'
              AND INDEX_NAME = 'idx_resume_achievements_country'
        ),
        'SELECT 1',
        'ALTER TABLE `resume_achievements` ADD INDEX `idx_resume_achievements_country` (`country_id`)'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'resume_achievements'
              AND INDEX_NAME = 'idx_resume_achievements_city'
        ),
        'SELECT 1',
        'ALTER TABLE `resume_achievements` ADD INDEX `idx_resume_achievements_city` (`city_id`)'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'resume_achievements'
              AND INDEX_NAME = 'idx_resume_achievements_award_level'
        ),
        'SELECT 1',
        'ALTER TABLE `resume_achievements` ADD INDEX `idx_resume_achievements_award_level` (`resume_id`, `award_level`)'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'resume_achievements'
              AND COLUMN_NAME = 'country_id'
              AND REFERENCED_TABLE_NAME = 'countries'
        ),
        'SELECT 1',
        'ALTER TABLE `resume_achievements` ADD CONSTRAINT `fk_resume_achievements_country`
            FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`)
            ON DELETE SET NULL ON UPDATE CASCADE'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'resume_achievements'
              AND COLUMN_NAME = 'city_id'
              AND REFERENCED_TABLE_NAME = 'cities'
        ),
        'SELECT 1',
        'ALTER TABLE `resume_achievements` ADD CONSTRAINT `fk_resume_achievements_city`
            FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`)
            ON DELETE SET NULL ON UPDATE CASCADE'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

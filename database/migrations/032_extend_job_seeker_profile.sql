-- =============================================================================
-- Migration : 032_extend_job_seeker_profile
-- Project   : JobVisa.lk Enterprise
-- Purpose   : Extend profile / education / language columns for Sprint 2C
--             without altering earlier migration files.
-- Depends   : 009, 014, 017, 001
-- =============================================================================

ALTER TABLE `user_profiles`
    ADD COLUMN IF NOT EXISTS `first_name` VARCHAR(80) NULL AFTER `user_id`,
    ADD COLUMN IF NOT EXISTS `last_name` VARCHAR(80) NULL AFTER `first_name`,
    ADD COLUMN IF NOT EXISTS `nic_passport` VARCHAR(64) NULL AFTER `last_name`,
    ADD COLUMN IF NOT EXISTS `marital_status` VARCHAR(32) NULL AFTER `gender`,
    ADD COLUMN IF NOT EXISTS `expected_salary` DECIMAL(12,2) NULL AFTER `marital_status`,
    ADD COLUMN IF NOT EXISTS `current_country_id` BIGINT UNSIGNED NULL AFTER `nationality_country_id`,
    ADD COLUMN IF NOT EXISTS `address` TEXT NULL AFTER `current_city_id`,
    ADD COLUMN IF NOT EXISTS `whatsapp` VARCHAR(32) NULL AFTER `address`;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'user_profiles'
              AND INDEX_NAME = 'idx_user_profiles_current_country'
        ),
        'SELECT 1',
        'ALTER TABLE `user_profiles` ADD INDEX `idx_user_profiles_current_country` (`current_country_id`)'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'user_profiles'
              AND COLUMN_NAME = 'current_country_id'
              AND REFERENCED_TABLE_NAME = 'countries'
        ),
        'SELECT 1',
        'ALTER TABLE `user_profiles` ADD CONSTRAINT `fk_user_profiles_current_country`
            FOREIGN KEY (`current_country_id`) REFERENCES `countries` (`id`)
            ON DELETE SET NULL ON UPDATE CASCADE'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

ALTER TABLE `education`
    ADD COLUMN IF NOT EXISTS `school` VARCHAR(200) NULL AFTER `resume_id`,
    ADD COLUMN IF NOT EXISTS `grade` VARCHAR(64) NULL AFTER `field_of_study`;

ALTER TABLE `user_languages`
    ADD COLUMN IF NOT EXISTS `speaking` VARCHAR(32) NULL AFTER `language_id`,
    ADD COLUMN IF NOT EXISTS `reading` VARCHAR(32) NULL AFTER `speaking`,
    ADD COLUMN IF NOT EXISTS `writing` VARCHAR(32) NULL AFTER `reading`;

-- Rollback: 043_extend_resume_achievements
-- Additive columns only; restores table shape from 042 without dropping the table.

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'resume_achievements'
              AND CONSTRAINT_NAME = 'fk_resume_achievements_city'
        ),
        'ALTER TABLE `resume_achievements` DROP FOREIGN KEY `fk_resume_achievements_city`',
        'SELECT 1'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'resume_achievements'
              AND CONSTRAINT_NAME = 'fk_resume_achievements_country'
        ),
        'ALTER TABLE `resume_achievements` DROP FOREIGN KEY `fk_resume_achievements_country`',
        'SELECT 1'
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
        'ALTER TABLE `resume_achievements` DROP INDEX `idx_resume_achievements_award_level`',
        'SELECT 1'
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
        'ALTER TABLE `resume_achievements` DROP INDEX `idx_resume_achievements_city`',
        'SELECT 1'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'resume_achievements'
              AND INDEX_NAME = 'idx_resume_achievements_country'
        ),
        'ALTER TABLE `resume_achievements` DROP INDEX `idx_resume_achievements_country`',
        'SELECT 1'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

ALTER TABLE `resume_achievements`
    DROP COLUMN IF EXISTS `certificate_size`,
    DROP COLUMN IF EXISTS `certificate_mime`,
    DROP COLUMN IF EXISTS `certificate_original_name`,
    DROP COLUMN IF EXISTS `remarks`,
    DROP COLUMN IF EXISTS `rank_or_placement`,
    DROP COLUMN IF EXISTS `award_level`,
    DROP COLUMN IF EXISTS `city_id`,
    DROP COLUMN IF EXISTS `country_id`;

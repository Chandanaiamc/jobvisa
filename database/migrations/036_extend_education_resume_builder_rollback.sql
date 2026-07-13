-- Rollback: 036_extend_education_resume_builder
-- Drops only columns/indexes added in 036. Existing education data preserved.

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'education'
              AND CONSTRAINT_NAME = 'fk_education_country'
        ),
        'ALTER TABLE `education` DROP FOREIGN KEY `fk_education_country`',
        'SELECT 1'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'education'
              AND INDEX_NAME = 'idx_education_country_id'
        ),
        'ALTER TABLE `education` DROP INDEX `idx_education_country_id`',
        'SELECT 1'
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
        'ALTER TABLE `education` DROP INDEX `idx_education_deleted_at`',
        'SELECT 1'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

ALTER TABLE `education`
    DROP COLUMN IF EXISTS `deleted_at`,
    DROP COLUMN IF EXISTS `status`,
    DROP COLUMN IF EXISTS `city`,
    DROP COLUMN IF EXISTS `country_id`,
    DROP COLUMN IF EXISTS `qualification_type`;

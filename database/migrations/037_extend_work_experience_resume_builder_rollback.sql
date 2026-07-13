-- Rollback: 037_extend_work_experience_resume_builder
-- Drops only objects added in 037. Existing work_experience core columns preserved.

DROP TABLE IF EXISTS `work_experience_skills`;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'work_experience'
              AND INDEX_NAME = 'idx_work_experience_deleted_at'
        ),
        'ALTER TABLE `work_experience` DROP INDEX `idx_work_experience_deleted_at`',
        'SELECT 1'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

ALTER TABLE `work_experience`
    DROP COLUMN IF EXISTS `deleted_at`,
    DROP COLUMN IF EXISTS `status`,
    DROP COLUMN IF EXISTS `supervisor_contact`,
    DROP COLUMN IF EXISTS `supervisor_name`,
    DROP COLUMN IF EXISTS `reason_for_leaving`,
    DROP COLUMN IF EXISTS `achievements`,
    DROP COLUMN IF EXISTS `responsibilities`,
    DROP COLUMN IF EXISTS `city`,
    DROP COLUMN IF EXISTS `industry`,
    DROP COLUMN IF EXISTS `employment_type`;

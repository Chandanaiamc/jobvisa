-- Rollback: 033_extend_resumes_foundation

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'resumes'
              AND INDEX_NAME = 'idx_resumes_user_status'
        ),
        'ALTER TABLE `resumes` DROP INDEX `idx_resumes_user_status`',
        'SELECT 1'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'resumes'
              AND INDEX_NAME = 'idx_resumes_deleted_at'
        ),
        'ALTER TABLE `resumes` DROP INDEX `idx_resumes_deleted_at`',
        'SELECT 1'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

ALTER TABLE `resumes`
    DROP COLUMN IF EXISTS `deleted_at`,
    DROP COLUMN IF EXISTS `visibility`,
    DROP COLUMN IF EXISTS `status`;

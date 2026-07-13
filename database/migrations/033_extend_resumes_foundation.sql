-- =============================================================================
-- Migration : 033_extend_resumes_foundation
-- Project   : JobVisa.lk Enterprise
-- Purpose   : Sprint 2D.1 — status, visibility, soft delete for resumes
-- Depends   : 013_create_resumes_table
-- =============================================================================

ALTER TABLE `resumes`
    ADD COLUMN IF NOT EXISTS `status` VARCHAR(32) NOT NULL DEFAULT 'draft'
        COMMENT 'draft|published' AFTER `title`,
    ADD COLUMN IF NOT EXISTS `visibility` VARCHAR(32) NOT NULL DEFAULT 'employers'
        COMMENT 'public|employers|private' AFTER `status`,
    ADD COLUMN IF NOT EXISTS `deleted_at` DATETIME(3) NULL DEFAULT NULL AFTER `updated_at`;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'resumes'
              AND INDEX_NAME = 'idx_resumes_user_status'
        ),
        'SELECT 1',
        'ALTER TABLE `resumes` ADD INDEX `idx_resumes_user_status` (`user_id`, `status`)'
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
        'SELECT 1',
        'ALTER TABLE `resumes` ADD INDEX `idx_resumes_deleted_at` (`deleted_at`)'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

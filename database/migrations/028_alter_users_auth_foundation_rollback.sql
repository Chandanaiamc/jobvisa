-- =============================================================================
-- Rollback : 028_alter_users_auth_foundation
-- Removes auth foundation columns/indexes/FK added by 028.
-- Does not drop legacy `role` or pre-existing `last_login_at` from 008.
-- =============================================================================

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'users'
              AND CONSTRAINT_NAME = 'fk_users_auth_role_id'
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ),
        'ALTER TABLE `users` DROP FOREIGN KEY `fk_users_auth_role_id`',
        'SELECT 1'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'users'
              AND INDEX_NAME = 'idx_users_auth_role_id_deleted'
        ),
        'ALTER TABLE `users` DROP INDEX `idx_users_auth_role_id_deleted`',
        'SELECT 1'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'users'
              AND INDEX_NAME = 'idx_users_auth_deleted_at'
        ),
        'ALTER TABLE `users` DROP INDEX `idx_users_auth_deleted_at`',
        'SELECT 1'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'users'
              AND INDEX_NAME = 'idx_users_auth_remember_token'
        ),
        'ALTER TABLE `users` DROP INDEX `idx_users_auth_remember_token`',
        'SELECT 1'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'users'
              AND INDEX_NAME = 'idx_users_auth_role_id'
        ),
        'ALTER TABLE `users` DROP INDEX `idx_users_auth_role_id`',
        'SELECT 1'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

ALTER TABLE `users` DROP COLUMN IF EXISTS `deleted_at`;
ALTER TABLE `users` DROP COLUMN IF EXISTS `remember_token`;
ALTER TABLE `users` DROP COLUMN IF EXISTS `role_id`;
-- Intentionally NOT dropping `last_login_at` (may originate from 008)

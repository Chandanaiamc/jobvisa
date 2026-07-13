-- =============================================================================
-- Migration : 028_alter_users_auth_foundation
-- Project   : JobVisa.lk Enterprise
-- Target    : MariaDB 10.4+
-- Purpose   : Add role_id, remember_token, deleted_at; ensure last_login_at;
--             FK to roles; indexes. Preserves legacy string `role` column.
-- Depends   : 008_create_users_table, 027_create_roles_table
-- =============================================================================

ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `role_id` BIGINT UNSIGNED NULL AFTER `role`,
    ADD COLUMN IF NOT EXISTS `remember_token` VARCHAR(255) NULL AFTER `password_hash`,
    ADD COLUMN IF NOT EXISTS `deleted_at` TIMESTAMP NULL DEFAULT NULL AFTER `updated_at`;

-- last_login_at may already exist from 008 (DATETIME); add only if missing
ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `last_login_at` TIMESTAMP NULL DEFAULT NULL;

-- Normalize approved shapes without dropping data
ALTER TABLE `users`
    MODIFY COLUMN `role_id` BIGINT UNSIGNED NULL,
    MODIFY COLUMN `remember_token` VARCHAR(255) NULL;

-- Useful indexes (unique names to avoid clashing with earlier 024/026 index names)
SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'users'
              AND INDEX_NAME = 'idx_users_auth_role_id'
        ),
        'SELECT 1',
        'ALTER TABLE `users` ADD INDEX `idx_users_auth_role_id` (`role_id`)'
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
        'SELECT 1',
        'ALTER TABLE `users` ADD INDEX `idx_users_auth_remember_token` (`remember_token`(100))'
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
        'SELECT 1',
        'ALTER TABLE `users` ADD INDEX `idx_users_auth_deleted_at` (`deleted_at`)'
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
        'SELECT 1',
        'ALTER TABLE `users` ADD INDEX `idx_users_auth_role_id_deleted` (`role_id`, `deleted_at`)'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Foreign key to roles (skip if an FK on users.role_id already exists)
SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'users'
              AND COLUMN_NAME = 'role_id'
              AND REFERENCED_TABLE_NAME = 'roles'
        ),
        'SELECT 1',
        'ALTER TABLE `users` ADD CONSTRAINT `fk_users_auth_role_id` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

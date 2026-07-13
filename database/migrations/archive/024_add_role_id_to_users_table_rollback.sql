-- =============================================================================
-- Rollback : 024_add_role_id_to_users_table
-- Restores pre-role_id users shape. Legacy `role` column remains untouched.
-- =============================================================================

ALTER TABLE `users` DROP FOREIGN KEY `fk_users_role`;
ALTER TABLE `users` DROP INDEX `idx_users_role_id_status`;
ALTER TABLE `users` DROP INDEX `idx_users_role_id`;
ALTER TABLE `users` DROP COLUMN `role_id`;

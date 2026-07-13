-- =============================================================================
-- Rollback : 026_add_remember_token_and_deleted_at_to_users
-- =============================================================================

ALTER TABLE `users` DROP INDEX `idx_users_status_deleted_at`;
ALTER TABLE `users` DROP INDEX `idx_users_deleted_at`;
ALTER TABLE `users` DROP INDEX `idx_users_remember_token`;
ALTER TABLE `users` DROP COLUMN `deleted_at`;
ALTER TABLE `users` DROP COLUMN `remember_token`;

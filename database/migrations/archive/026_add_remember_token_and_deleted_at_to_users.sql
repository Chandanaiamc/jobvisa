-- =============================================================================
-- Migration : 026_add_remember_token_and_deleted_at_to_users
-- Project   : JobVisa.lk
-- Target    : MariaDB 10.4+ compatible
-- Purpose   : Remember-me token column + soft delete timestamp
-- Notes     : Email UNIQUE remains on `email`. Soft-delete re-registration policy
--             is application-enforced (anonymize email on delete) — MariaDB 10.4
--             does not support partial unique indexes.
-- =============================================================================

ALTER TABLE `users`
    ADD COLUMN `remember_token` VARCHAR(100) DEFAULT NULL AFTER `password_hash`,
    ADD COLUMN `deleted_at` DATETIME(3) DEFAULT NULL AFTER `updated_at`;

ALTER TABLE `users`
    ADD KEY `idx_users_remember_token` (`remember_token`),
    ADD KEY `idx_users_deleted_at` (`deleted_at`),
    ADD KEY `idx_users_status_deleted_at` (`status`, `deleted_at`);

-- Backfill soft-delete timestamp for rows already marked deleted via status
UPDATE `users`
SET `deleted_at` = `updated_at`
WHERE `status` = 'deleted'
  AND `deleted_at` IS NULL;

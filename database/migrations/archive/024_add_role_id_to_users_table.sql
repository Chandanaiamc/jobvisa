-- =============================================================================
-- Migration : 024_add_role_id_to_users_table
-- Project   : JobVisa.lk
-- Target    : MariaDB 10.4+ compatible
-- Purpose   : Replace string role authority with roles.id foreign key
-- Notes     : Legacy `users.role` VARCHAR is retained for compatibility/backfill
-- Depends   : 008_create_users_table, 023_create_roles_table
-- =============================================================================

-- 1) Add nullable role_id for safe backfill
ALTER TABLE `users`
    ADD COLUMN `role_id` BIGINT UNSIGNED NULL AFTER `role`;

-- 2) Backfill from legacy string role → roles.slug
UPDATE `users` AS `u`
INNER JOIN `roles` AS `r` ON `r`.`slug` = `u`.`role`
SET `u`.`role_id` = `r`.`id`
WHERE `u`.`role_id` IS NULL;

-- 3) Default any unmatched rows to seeker
UPDATE `users` AS `u`
SET `u`.`role_id` = (
    SELECT `r`.`id` FROM `roles` AS `r` WHERE `r`.`slug` = 'seeker' LIMIT 1
)
WHERE `u`.`role_id` IS NULL;

-- 4) Enforce NOT NULL + FK + indexes
ALTER TABLE `users`
    MODIFY COLUMN `role_id` BIGINT UNSIGNED NOT NULL,
    ADD KEY `idx_users_role_id` (`role_id`),
    ADD KEY `idx_users_role_id_status` (`role_id`, `status`),
    ADD CONSTRAINT `fk_users_role`
        FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE;

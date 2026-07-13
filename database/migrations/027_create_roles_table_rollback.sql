-- =============================================================================
-- Rollback : 027_create_roles_table
-- WARNING  : Run only if no users.role_id FK references roles.
--            Prefer rolling back 028 first.
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `roles`;
SET FOREIGN_KEY_CHECKS = 1;

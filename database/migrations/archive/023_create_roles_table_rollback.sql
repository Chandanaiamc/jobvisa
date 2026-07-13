-- =============================================================================
-- Rollback : 023_create_roles_table
-- WARNING  : Fails if users.role_id FK still references roles. Run 024 rollback first.
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `roles`;
SET FOREIGN_KEY_CHECKS = 1;

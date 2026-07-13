-- =============================================================================
-- Rollback : 062_create_job_search_copilot_tables
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `job_search_copilot_history`;
DROP TABLE IF EXISTS `job_search_copilot_plans`;
SET FOREIGN_KEY_CHECKS = 1;

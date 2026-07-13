-- =============================================================================
-- Rollback : 063_create_offer_evaluation_tables
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `offer_evaluation_history`;
DROP TABLE IF EXISTS `offer_evaluation_analyses`;
SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- Rollback : 029_create_password_reset_tokens_table
-- Drops password_reset_tokens (including any legacy 025 instance of this table).
-- =============================================================================

DROP TABLE IF EXISTS `password_reset_tokens`;

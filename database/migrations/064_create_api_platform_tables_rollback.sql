-- =============================================================================
-- Rollback : 064_create_api_platform_tables
-- =============================================================================

DROP TABLE IF EXISTS `api_webhook_deliveries`;
DROP TABLE IF EXISTS `api_webhook_subscriptions`;
DROP TABLE IF EXISTS `api_audit_logs`;
DROP TABLE IF EXISTS `api_personal_access_tokens`;

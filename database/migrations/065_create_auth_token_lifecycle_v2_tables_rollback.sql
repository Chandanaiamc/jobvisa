-- 065_create_auth_token_lifecycle_v2_tables_rollback.sql

DROP TABLE IF EXISTS `auth_mfa_factors`;
DROP TABLE IF EXISTS `auth_refresh_tokens`;
DROP TABLE IF EXISTS `auth_devices`;

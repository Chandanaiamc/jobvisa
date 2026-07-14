-- 065_create_auth_token_lifecycle_v2_tables.sql
-- Authentication & Token Lifecycle v2: refresh tokens, devices, MFA-ready factors.

CREATE TABLE IF NOT EXISTS `auth_devices` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `fingerprint_hash` CHAR(64) NOT NULL,
  `name` VARCHAR(120) NOT NULL DEFAULT 'Unknown device',
  `platform` VARCHAR(80) NULL,
  `last_ip` VARCHAR(45) NULL,
  `last_user_agent` VARCHAR(512) NULL,
  `last_seen_at` DATETIME(3) NULL,
  `revoked_at` DATETIME(3) NULL,
  `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_auth_devices_user_fp` (`user_id`, `fingerprint_hash`),
  KEY `idx_auth_devices_user` (`user_id`, `revoked_at`),
  CONSTRAINT `fk_auth_devices_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `auth_refresh_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `device_id` BIGINT UNSIGNED NULL,
  `family_id` CHAR(36) NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `token_prefix` VARCHAR(16) NOT NULL DEFAULT '',
  `access_token_id` BIGINT UNSIGNED NULL,
  `expires_at` DATETIME(3) NOT NULL,
  `rotated_at` DATETIME(3) NULL,
  `revoked_at` DATETIME(3) NULL,
  `replaced_by_id` BIGINT UNSIGNED NULL,
  `last_used_at` DATETIME(3) NULL,
  `last_used_ip` VARCHAR(45) NULL,
  `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_auth_refresh_hash` (`token_hash`),
  KEY `idx_auth_refresh_family` (`family_id`, `revoked_at`),
  KEY `idx_auth_refresh_user` (`user_id`, `revoked_at`),
  KEY `idx_auth_refresh_device` (`device_id`),
  CONSTRAINT `fk_auth_refresh_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_auth_refresh_device` FOREIGN KEY (`device_id`) REFERENCES `auth_devices` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `auth_mfa_factors` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `type` VARCHAR(32) NOT NULL DEFAULT 'totp',
  `label` VARCHAR(120) NOT NULL DEFAULT 'Authenticator',
  `secret_hash` CHAR(64) NULL COMMENT 'Never store raw TOTP secrets; optional hash/placeholder for MFA-ready',
  `enabled_at` DATETIME(3) NULL,
  `verified_at` DATETIME(3) NULL,
  `revoked_at` DATETIME(3) NULL,
  `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  KEY `idx_auth_mfa_user` (`user_id`, `revoked_at`),
  CONSTRAINT `fk_auth_mfa_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- Migration : 027_create_roles_table
-- Project   : JobVisa.lk Enterprise
-- Target    : MariaDB 10.4+ (InnoDB, utf8mb4_unicode_ci)
-- Purpose   : Approved authentication foundation — normalized roles table
-- Notes     : No role seed data. Does not delete prior migrations (023+).
-- =============================================================================

CREATE TABLE IF NOT EXISTS `roles` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) NULL,
    `is_system` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_roles_slug` (`slug`),
    KEY `idx_roles_name` (`name`),
    KEY `idx_roles_is_system` (`is_system`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Align older 023 definition to approved column sizes/defaults when table already existed
ALTER TABLE `roles`
    MODIFY COLUMN `name` VARCHAR(100) NOT NULL,
    MODIFY COLUMN `slug` VARCHAR(100) NOT NULL,
    MODIFY COLUMN `description` VARCHAR(255) NULL,
    MODIFY COLUMN `is_system` TINYINT(1) NOT NULL DEFAULT 0;

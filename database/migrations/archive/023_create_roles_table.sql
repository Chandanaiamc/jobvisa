-- =============================================================================
-- Migration : 023_create_roles_table
-- Project   : JobVisa.lk
-- Target    : MariaDB 10.4+ compatible (InnoDB, utf8mb4)
-- Purpose   : Normalized RBAC roles master table + system role seed
-- =============================================================================

CREATE TABLE IF NOT EXISTS `roles` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `slug` VARCHAR(50) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `is_system` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = protected platform role',
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_roles_slug` (`slug`),
    UNIQUE KEY `uq_roles_name` (`name`),
    KEY `idx_roles_is_system` (`is_system`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Idempotent system role seed (required for users.role_id FK)
INSERT INTO `roles` (`name`, `slug`, `description`, `is_system`)
SELECT `v`.`name`, `v`.`slug`, `v`.`description`, `v`.`is_system`
FROM (
    SELECT 'Seeker' AS `name`, 'seeker' AS `slug`, 'Job seeker account' AS `description`, 1 AS `is_system`
    UNION ALL SELECT 'Employer', 'employer', 'Employer / recruiter account', 1
    UNION ALL SELECT 'Admin', 'admin', 'Platform administrator', 1
    UNION ALL SELECT 'Staff', 'staff', 'Internal operations staff', 1
) AS `v`
LEFT JOIN `roles` AS `r` ON `r`.`slug` = `v`.`slug`
WHERE `r`.`id` IS NULL;

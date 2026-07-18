-- =============================================================================
-- Migration : 067_create_application_status_history
-- Project   : JobVisa.lk Enterprise
-- Purpose   : Phase 1 — application status transition audit trail
-- Depends   : applications, users
-- Preserves : no destructive changes to applications
-- =============================================================================

CREATE TABLE IF NOT EXISTS `application_status_history` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `application_id` BIGINT UNSIGNED NOT NULL,
    `from_status` VARCHAR(32) DEFAULT NULL,
    `to_status` VARCHAR(32) NOT NULL,
    `actor_user_id` BIGINT UNSIGNED DEFAULT NULL,
    `note` VARCHAR(500) DEFAULT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    KEY `idx_ash_application_created` (`application_id`, `created_at`),
    KEY `idx_ash_actor` (`actor_user_id`),
    CONSTRAINT `fk_ash_application`
        FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_ash_actor`
        FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

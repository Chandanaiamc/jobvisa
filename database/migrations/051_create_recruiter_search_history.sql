-- =============================================================================
-- Migration : 051_create_recruiter_search_history
-- Project   : JobVisa.lk Enterprise
-- Purpose   : Sprint 2F.6 — AI Recruiter Assistant search history
-- Depends   : users
-- Preserves : no destructive changes to prior tables
-- =============================================================================

CREATE TABLE IF NOT EXISTS `recruiter_search_history` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `employer_user_id` BIGINT UNSIGNED NOT NULL,
    `query_text` VARCHAR(500) NOT NULL,
    `parsed_filters` JSON NOT NULL,
    `result_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `top_result_json` JSON NULL,
    `suggestions_json` JSON NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    `deleted_at` DATETIME(3) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_rsh_employer_created` (`employer_user_id`, `created_at`, `deleted_at`),
    KEY `idx_rsh_employer_deleted` (`employer_user_id`, `deleted_at`),
    CONSTRAINT `fk_rsh_employer_user`
        FOREIGN KEY (`employer_user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

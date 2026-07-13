-- =============================================================================
-- Migration : 055_create_cover_letter_tables
-- Project   : JobVisa.lk Enterprise
-- Purpose   : Sprint 3.1 — AI Cover Letter versions + history
-- Depends   : resumes, users, jobs
-- Preserves : no destructive changes to prior tables
-- =============================================================================

CREATE TABLE IF NOT EXISTS `cover_letter_versions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `job_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `version_label` VARCHAR(120) NOT NULL,
    `status` VARCHAR(32) NOT NULL DEFAULT 'preview',
    `style` VARCHAR(32) NOT NULL DEFAULT 'professional',
    `tone` VARCHAR(64) NULL DEFAULT NULL,
    `body_text` MEDIUMTEXT NOT NULL,
    `highlights_json` JSON NOT NULL,
    `context_json` JSON NOT NULL,
    `ats_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `rules_version` VARCHAR(32) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    `deleted_at` DATETIME(3) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_clv_resume_status` (`resume_id`, `status`, `deleted_at`),
    KEY `idx_clv_resume_style` (`resume_id`, `style`, `deleted_at`),
    KEY `idx_clv_job` (`job_id`, `deleted_at`),
    KEY `idx_clv_user` (`user_id`, `deleted_at`),
    CONSTRAINT `fk_clv_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_clv_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_clv_job`
        FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cover_letter_history` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `version_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `action` VARCHAR(32) NOT NULL DEFAULT 'generate',
    `style` VARCHAR(32) NULL DEFAULT NULL,
    `headline` VARCHAR(255) NOT NULL DEFAULT '',
    `snapshot_json` JSON NOT NULL,
    `ats_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `rules_version` VARCHAR(32) NOT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    `deleted_at` DATETIME(3) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_clh_resume_created` (`resume_id`, `created_at`, `deleted_at`),
    KEY `idx_clh_resume_deleted` (`resume_id`, `deleted_at`),
    KEY `idx_clh_version` (`version_id`),
    CONSTRAINT `fk_clh_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_clh_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_clh_version`
        FOREIGN KEY (`version_id`) REFERENCES `cover_letter_versions` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

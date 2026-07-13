-- =============================================================================
-- Migration : 054_create_ai_resume_builder_tables
-- Project   : JobVisa.lk Enterprise
-- Purpose   : Sprint 2F.9 — AI Resume Builder versions + generation history
-- Depends   : resumes, users
-- Preserves : no destructive changes to prior tables
-- =============================================================================

CREATE TABLE IF NOT EXISTS `ai_resume_versions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `version_label` VARCHAR(120) NOT NULL,
    `status` VARCHAR(32) NOT NULL DEFAULT 'preview',
    `target_role` VARCHAR(191) NULL DEFAULT NULL,
    `professional_summary` TEXT NOT NULL,
    `content_json` JSON NOT NULL,
    `ats_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `missing_keywords_json` JSON NOT NULL,
    `keyword_suggestions_json` JSON NOT NULL,
    `builder_version` VARCHAR(32) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    `deleted_at` DATETIME(3) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_arv_resume_status` (`resume_id`, `status`, `deleted_at`),
    KEY `idx_arv_resume_active` (`resume_id`, `is_active`, `deleted_at`),
    KEY `idx_arv_user` (`user_id`, `deleted_at`),
    KEY `idx_arv_ats` (`resume_id`, `ats_score`),
    CONSTRAINT `fk_arv_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_arv_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ai_resume_builder_history` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `version_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `action` VARCHAR(32) NOT NULL DEFAULT 'generate',
    `headline` VARCHAR(255) NOT NULL DEFAULT '',
    `snapshot_json` JSON NOT NULL,
    `ats_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `builder_version` VARCHAR(32) NOT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    `deleted_at` DATETIME(3) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_arbh_resume_created` (`resume_id`, `created_at`, `deleted_at`),
    KEY `idx_arbh_resume_deleted` (`resume_id`, `deleted_at`),
    KEY `idx_arbh_version` (`version_id`),
    CONSTRAINT `fk_arbh_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_arbh_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_arbh_version`
        FOREIGN KEY (`version_id`) REFERENCES `ai_resume_versions` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

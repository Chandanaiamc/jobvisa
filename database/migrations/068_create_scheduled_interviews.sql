-- =============================================================================
-- Migration : 068_create_scheduled_interviews
-- Project   : JobVisa.lk Enterprise
-- Purpose   : Phase 1 — interview scheduling (Option B)
-- Depends   : applications, jobs, users
-- Preserves : interview_sessions (052 Interview Assistant) untouched
-- =============================================================================

CREATE TABLE IF NOT EXISTS `scheduled_interviews` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `application_id` BIGINT UNSIGNED NOT NULL,
    `job_id` BIGINT UNSIGNED NOT NULL,
    `employer_user_id` BIGINT UNSIGNED NOT NULL,
    `candidate_user_id` BIGINT UNSIGNED NOT NULL,
    `status` VARCHAR(32) NOT NULL DEFAULT 'proposed'
        COMMENT 'proposed|confirmed|declined|cancelled|completed',
    `scheduled_at_utc` DATETIME(3) NOT NULL,
    `duration_minutes` SMALLINT UNSIGNED NOT NULL DEFAULT 60,
    `timezone` VARCHAR(64) NOT NULL COMMENT 'IANA timezone e.g. Asia/Colombo',
    `location_type` VARCHAR(32) NOT NULL DEFAULT 'other'
        COMMENT 'onsite|phone|other',
    `location_notes` VARCHAR(500) DEFAULT NULL,
    `round_number` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `cancelled_at` DATETIME(3) DEFAULT NULL,
    `completed_at` DATETIME(3) DEFAULT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    KEY `idx_si_application_status` (`application_id`, `status`),
    KEY `idx_si_employer_scheduled` (`employer_user_id`, `scheduled_at_utc`),
    KEY `idx_si_candidate_scheduled` (`candidate_user_id`, `scheduled_at_utc`),
    KEY `idx_si_job` (`job_id`),
    CONSTRAINT `fk_si_application`
        FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_si_job`
        FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_si_employer_user`
        FOREIGN KEY (`employer_user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_si_candidate_user`
        FOREIGN KEY (`candidate_user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `scheduled_interview_history` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `interview_id` BIGINT UNSIGNED NOT NULL,
    `from_status` VARCHAR(32) DEFAULT NULL,
    `to_status` VARCHAR(32) NOT NULL,
    `actor_user_id` BIGINT UNSIGNED DEFAULT NULL,
    `note` VARCHAR(500) DEFAULT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    KEY `idx_sih_interview_created` (`interview_id`, `created_at`),
    KEY `idx_sih_actor` (`actor_user_id`),
    CONSTRAINT `fk_sih_interview`
        FOREIGN KEY (`interview_id`) REFERENCES `scheduled_interviews` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sih_actor`
        FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

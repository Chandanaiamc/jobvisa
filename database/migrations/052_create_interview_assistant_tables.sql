-- =============================================================================
-- Migration : 052_create_interview_assistant_tables
-- Project   : JobVisa.lk Enterprise
-- Purpose   : Sprint 2F.7 — AI Interview Assistant sessions + scorecards
-- Depends   : users, jobs, applications, resumes
-- Preserves : no destructive changes to prior tables
-- =============================================================================

CREATE TABLE IF NOT EXISTS `interview_sessions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `employer_user_id` BIGINT UNSIGNED NOT NULL,
    `job_id` BIGINT UNSIGNED NOT NULL,
    `application_id` BIGINT UNSIGNED NOT NULL,
    `resume_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `candidate_user_id` BIGINT UNSIGNED NOT NULL,
    `status` VARCHAR(32) NOT NULL DEFAULT 'prepared',
    `technical_questions` JSON NOT NULL,
    `behavioral_questions` JSON NOT NULL,
    `strengths_json` JSON NOT NULL,
    `weaknesses_json` JSON NOT NULL,
    `recommendations_json` JSON NOT NULL,
    `context_scores_json` JSON NOT NULL,
    `assistant_version` VARCHAR(32) NOT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    `deleted_at` DATETIME(3) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_is_employer_created` (`employer_user_id`, `created_at`, `deleted_at`),
    KEY `idx_is_employer_deleted` (`employer_user_id`, `deleted_at`),
    KEY `idx_is_job` (`job_id`, `deleted_at`),
    KEY `idx_is_application` (`application_id`, `deleted_at`),
    KEY `idx_is_candidate` (`candidate_user_id`, `deleted_at`),
    CONSTRAINT `fk_is_employer_user`
        FOREIGN KEY (`employer_user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_is_job`
        FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_is_application`
        FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_is_candidate_user`
        FOREIGN KEY (`candidate_user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_is_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `interview_scorecards` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `technical_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `behavioral_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `communication_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `culture_fit_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `overall_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `notes` TEXT NULL,
    `hiring_recommendation` VARCHAR(32) NOT NULL DEFAULT 'pending',
    `scored_by_user_id` BIGINT UNSIGNED NOT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_interview_scorecard_session` (`session_id`),
    KEY `idx_isc_scored_by` (`scored_by_user_id`),
    KEY `idx_isc_recommendation` (`hiring_recommendation`),
    CONSTRAINT `fk_isc_session`
        FOREIGN KEY (`session_id`) REFERENCES `interview_sessions` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_isc_scored_by`
        FOREIGN KEY (`scored_by_user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

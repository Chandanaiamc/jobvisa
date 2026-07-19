-- =============================================================================
-- Migration : 070_create_hire_completions
-- Project   : JobVisa.lk Enterprise
-- Purpose   : Phase 1 — hiring completion after offer accept / employer hire (Option B)
-- Depends   : applications, jobs, users, job_offers (069)
-- Preserves : Offer Evaluation, Interview Assistant, soft status only
-- =============================================================================

CREATE TABLE IF NOT EXISTS `hire_completions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `application_id` BIGINT UNSIGNED NOT NULL,
    `job_id` BIGINT UNSIGNED NOT NULL,
    `employer_user_id` BIGINT UNSIGNED NOT NULL,
    `candidate_user_id` BIGINT UNSIGNED NOT NULL,
    `offer_id` BIGINT UNSIGNED DEFAULT NULL,
    `status` VARCHAR(32) NOT NULL DEFAULT 'pending'
        COMMENT 'pending|confirmed|completed|cancelled',
    `start_date` DATE DEFAULT NULL,
    `notes` VARCHAR(500) DEFAULT NULL,
    `confirmed_at` DATETIME(3) DEFAULT NULL,
    `completed_at` DATETIME(3) DEFAULT NULL,
    `cancelled_at` DATETIME(3) DEFAULT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_hc_application` (`application_id`),
    UNIQUE KEY `uq_hc_offer` (`offer_id`),
    KEY `idx_hc_employer_status` (`employer_user_id`, `status`),
    KEY `idx_hc_candidate_status` (`candidate_user_id`, `status`),
    KEY `idx_hc_job` (`job_id`),
    CONSTRAINT `fk_hc_application`
        FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_hc_job`
        FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_hc_employer_user`
        FOREIGN KEY (`employer_user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_hc_candidate_user`
        FOREIGN KEY (`candidate_user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_hc_offer`
        FOREIGN KEY (`offer_id`) REFERENCES `job_offers` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hire_completion_history` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `hire_completion_id` BIGINT UNSIGNED NOT NULL,
    `from_status` VARCHAR(32) DEFAULT NULL,
    `to_status` VARCHAR(32) NOT NULL,
    `actor_user_id` BIGINT UNSIGNED DEFAULT NULL,
    `note` VARCHAR(500) DEFAULT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    KEY `idx_hch_hire_created` (`hire_completion_id`, `created_at`),
    KEY `idx_hch_actor` (`actor_user_id`),
    CONSTRAINT `fk_hch_hire`
        FOREIGN KEY (`hire_completion_id`) REFERENCES `hire_completions` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_hch_actor`
        FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

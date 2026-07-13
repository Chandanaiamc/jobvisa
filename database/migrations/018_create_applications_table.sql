-- Migration: create applications table
-- JobVisa.lk

CREATE TABLE IF NOT EXISTS `applications` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `job_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `resume_id` BIGINT UNSIGNED DEFAULT NULL,
    `cover_letter` TEXT DEFAULT NULL,
    `status` VARCHAR(32) NOT NULL DEFAULT 'submitted' COMMENT 'submitted|reviewing|shortlisted|rejected|hired|withdrawn',
    `employer_notes` TEXT DEFAULT NULL,
    `applied_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `status_updated_at` DATETIME(3) DEFAULT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_applications_job_user` (`job_id`, `user_id`),
    KEY `idx_applications_user_applied` (`user_id`, `applied_at`),
    KEY `idx_applications_job_status` (`job_id`, `status`),
    KEY `idx_applications_resume_id` (`resume_id`),
    KEY `idx_applications_status_applied` (`status`, `applied_at`),
    CONSTRAINT `fk_applications_job`
        FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_applications_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_applications_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

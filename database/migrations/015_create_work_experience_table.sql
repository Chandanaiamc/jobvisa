-- Migration: create work_experience table
-- JobVisa.lk

CREATE TABLE IF NOT EXISTS `work_experience` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `company_name` VARCHAR(200) NOT NULL,
    `job_title` VARCHAR(150) NOT NULL,
    `country_id` BIGINT UNSIGNED DEFAULT NULL,
    `start_date` DATE DEFAULT NULL,
    `end_date` DATE DEFAULT NULL,
    `is_current` TINYINT(1) NOT NULL DEFAULT 0,
    `description` TEXT DEFAULT NULL,
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    KEY `idx_work_experience_resume_id` (`resume_id`),
    KEY `idx_work_experience_country_id` (`country_id`),
    KEY `idx_work_experience_resume_sort` (`resume_id`, `sort_order`),
    CONSTRAINT `fk_work_experience_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_work_experience_country`
        FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

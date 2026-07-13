-- Migration: create education table
-- JobVisa.lk

CREATE TABLE IF NOT EXISTS `education` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resume_id` BIGINT UNSIGNED NOT NULL,
    `institution` VARCHAR(200) NOT NULL,
    `degree` VARCHAR(150) NOT NULL,
    `field_of_study` VARCHAR(150) DEFAULT NULL,
    `start_date` DATE DEFAULT NULL,
    `end_date` DATE DEFAULT NULL,
    `is_current` TINYINT(1) NOT NULL DEFAULT 0,
    `description` TEXT DEFAULT NULL,
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (`id`),
    KEY `idx_education_resume_id` (`resume_id`),
    KEY `idx_education_resume_sort` (`resume_id`, `sort_order`),
    CONSTRAINT `fk_education_resume`
        FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
